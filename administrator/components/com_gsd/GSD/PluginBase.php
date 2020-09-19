<?php

/**
 * @package         Google Structured Data
 * @version         4.8.0-RC1 Pro
 * 
 * @author          Tassos Marinos <info@tassos.gr>
 * @link            http://www.tassos.gr
 * @copyright       Copyright © 2018 Tassos Marinos All Rights Reserved
 * @license         GNU GPLv3 <http://www.gnu.org/licenses/gpl.html> or later
*/

namespace GSD;

defined('_JEXEC') or die('Restricted access');

use GSD\Json;
use GSD\Helper;
use GSD\MappingOptions;
use NRFramework\Cache;
use NRFramework\Assignments;
use Joomla\Registry\Registry;
use Joomla\String\StringHelper;

/**
 *  Google Structured Data helper class
 */
class PluginBase extends \JPlugin
{
    /**
     *  Auto load the plugin language file
     *
     *  @var  boolean
     */
    protected $autoloadLanguage = true;

    /**
     *  Joomla Application Object
     *
     *  @var  object
     */
    protected $app;

    /**
     *  Joomla Database Object
     *
     *  @var  object
     */
    protected $db;

    /**
     *  Holds all available snippets for the current active page.
     *
     *  @var  array
     */
    protected $snippets;

    /**
     *  Indicates the query string parameter name that is used by the front-end component
     *
     *  @var  string
     */
    protected $thingRequestIDName = 'id';

    /**
     *  Indicates the request variable name used by plugin's assosiated component
     *
     *  @var  string
     */
    protected $thingRequestViewVar = 'view';

    /**
     *  Plugin constructor
     *
     *  @param  mixed   &$subject
     *  @param  array   $config
     */
    public function __construct(&$subject, $config = [])
    {
        // Load main language file
        \JFactory::getLanguage()->load('plg_system_gsd', JPATH_PLUGINS . '/system/gsd');

        // execute parent constructor
        parent::__construct($subject, $config);
    }

    /**
     *  Event triggered to gather all available plugins.
     *  Mostly used by the dropdowns in the backend.
     *
     *  @param   boolean  $mustBeInstalled  If enabled, the assosiated component must be installed
     *
     *  @return  array
     */
    public function onGSDGetType($mustBeInstalled = true)
    {
        if ($mustBeInstalled && !\NRFramework\Extension::isInstalled($this->_name))
        {
            return;
        }

        return [
            'name'  => \JText::_('PLG_GSD_' . strtoupper($this->_name) . '_ALIAS'),
            'alias' => $this->_name
        ];
    }

     /**
     *  Prepare form.
     *
     *  @param   JForm  $form  The form to be altered.
     *  @param   mixed  $data  The associated data for the form.
     *
     *  @return  boolean
     */
    public function onContentPrepareForm($form, $data)
    {
        // Make sure we are on the right context
        if ($this->app->isClient('site') || $form->getName() != 'com_gsd.item')
        {
            return;
        }

        if (is_null($data->plugin) || $data->plugin != $this->_name)
        {
            return;
        }

        // Load assignments xml file if it's available
        $assignmentsXML = JPATH_PLUGINS . '/gsd/' . $this->_name . '/form/assignments.xml'; 
        if (!\JFile::exists($assignmentsXML))
        {
            return;
        }

        $form->loadFile($assignmentsXML, false);
    }
    
    /**
     *  The event triggered before the JSON markup be appended to the document.
     *
     *  @param   array  &$data   The JSON snippets to be appended to the document
     *
     *  @return  void
     */
    public function onGSDBeforeRender(&$data)
    {
        // Quick filtering on component check
        if (!$this->passContext())
        {
            return;
        }

        // Let's check if the plugin supports the current component's view.
        if (!$payload = $this->getPayload())
        {
            return;
        }

        // Now, let's see if we have valid snippets for the active page. If not abort.
        if (!$this->snippets = $this->getSnippets())
        {
            $this->log('No valid items found');
            return;
        }

        // Prepare snippets
        foreach ($this->snippets as $snippet)
        {
            // Here, the payload must be merged with the snippet data
            $jsonData = $this->preparePayload($snippet, $payload);
            
            // Create JSON
            $jsonClass = new Json($jsonData);
            $json = $jsonClass->generate();

            // Add json back to main data object
            $data[] = $json;
        }
    }

    /**
     *  Validate context to decide whether the plugin should run or not.
     *
     *  @return   bool
     */
    protected function passContext()
    {
        return Helper::getComponentAlias() == $this->_name;
    }

    /**
     *  Get Item's ID
     *
     *  @return  string
     */
    protected function getThingID()
    {
        return $this->app->input->getInt($this->thingRequestIDName);
    }

    /**
     *  Get component's items and validate conditions
     *
     *  @return  Mixed   Null if no items found, The valid items array on success
     */
    protected function getSnippets()
    {
        \JModelLegacy::addIncludePath(JPATH_ADMINISTRATOR . '/components/com_gsd/models');

        $model = \JModelLegacy::getInstance('Items', 'GSDModel', ['ignore_request' => true]);
        $model->setState('filter.plugin', $this->_name);
        $model->setState('filter.state', 1);

        if (\JLanguageMultilang::isEnabled())
        {
            $model->setState('filter.language', [\JFactory::getLanguage()->getTag(), '*']);
        }

        if (!$rows = $model->getItems())
        {
            return;
        }

        // Check publishing assignments for each item
        foreach ($rows as $key => $row)
        {
            if (!isset($row->assignments) || !is_object($row->assignments))
            {
                continue;
            }

            // Prepare assignments
            $assignmentsFound = [];

            foreach ($row->assignments as $alias => $assignment)
            {
                if ($assignment->assignment_state == '0')
                {
                    continue;
                }

                // Remove unwanted assignments added by Free Pro code blocks
                if (strpos($alias, '@'))
                {
                    continue;
                }

                // If user hasn't made any selection, skip the assignment.
                if (!isset($assignment->selection))
                {
                    continue;
                }

                // Comply with the new conditions requirements
                $condition = (object) [
                    'alias'  => $alias,
                    'value'  => $assignment->selection,
                    'params' => isset($assignment->params) ? $assignment->params : [],
                    'assignment_state' => $assignment->assignment_state
                ];

                // Pass with 'AND' matching method. Hence the assignment to first [0] cell.
                $assignmentsFound[0][] = $condition;
            }

            // Validate assignments
            if (!$pass = (new Assignments())->passAll($assignmentsFound))
            {
                $this->log('Item #' . $row->id . ' does not pass the conditions check');
                unset($rows[$key]);
            }
        }

        $items = array_map(function($row)
        {
            $contentType = $row->contenttype;

            // After we have selected an Integration and a Content Type and we hit Save,
            // the item needs to be re-saved in order to access the Content Type options.
            //
            // We need to find a way to auto-populate the Content Type with default data during 1st save.
            //
            // A possible approach would be: Upon clicking on the New button, we display a popup modal where
            // the user can choose a Content Type, an Integration and a Title for the structured data item.
            // Then they will be redirected to the item editing page with these data prefilled.
            $contentTypeData = property_exists($row, $contentType) ? $row->{$contentType} : [];

            $s = new Registry($contentTypeData);
            $s->set('contentType', $contentType);

            // Help troubleshooting by logging item ID.
            $this->log('ID: ' . $row->id);

            return $s;
        }, $rows);

        return $items;
    }

    /**
     *  Asks for data from the child plugin based on the active view name
     *
     *  @return  Registry  The payload Registry
     */
    protected function getPayload()
    {
        $view   = $this->getView();
        $method = 'view' . ucfirst($view);

        if (!$view || !method_exists($this, $method))
        {
            $this->log('View ' . $view . ' is not supported');
            return;
        }

        // Yeah. Let's call the method. 
        $payload = $this->$method();

        // We need a valid array
        if (!is_array($payload))
        {  
            $this->log('Invalid Payload Array');
            return;
        }

        // Convert payload to Registry object and return it
        return new Registry($payload);
    }

    /**
     *  Prepares the payload to be used in the JSON class
     *
     *  @return  string
     */
    private function preparePayload($snippet, $payload)
    {   
        MappingOptions::prepare($snippet);

        // Create a new combined object by merging the snippet data into the payload
        // Note: In order to produce a valid merged object, payload's array keys should match the field names
        // as declared in the form's XML file.
        $p = clone $payload;
        $s = $p->merge($snippet, false);

        // Replace Smart Tags - This can be implemented with a Plugin
        $s = MappingOptions::replace($s, $payload);

        // Shorthand for the content type
        $contentType    = $snippet['contentType'];
        $prepareContent = Helper::getParams()->get('preparecontent', false);
        $descCharsLimit = Helper::getParams()->get('desclimit', 300);
        $strip_tags     = null;

        // The options below is a dirty and quick fix to allow HTML tags in the Job Posting Content Type.
        // The options below and most of the code in this method should be moved into a Content-Type specific namespaced class.
        if ($contentType == 'jobposting')
        {
            $strip_tags     = '<p><br><ul><li><h1><h2><h3><h4><h5><strong><em><b>';
            $descCharsLimit = 0;
        }

        // Content Preparation
        if ($prepareContent)
        {
            $s['headline']    = $this->prepareText($s['headline']);
            $s['description'] = $this->prepareText($s['description']);
        }

        // Prepare common data
        $commonData = [
            'contentType'   => $contentType,
            'id'            => $s['id'],
            'title'         => Helper::makeTextSafe($s['headline'], $strip_tags, 110),
            'description'   => Helper::makeTextSafe($s['description'], $strip_tags, $descCharsLimit),
            'image'         => Helper::absURL($s->get('image')),

            // Author / Publisher
            'authorName'    => $s['author'],

            // Rating
            'ratingValue'   => $s['rating_value'],
            'reviewCount'   => $s['review_count'],
            'bestRating'    => $s['bestRating'],
            'worstRating'   => $s['worstRating'],

            // Dates
            'datePublished' => Helper::date($s['publish_up'], true),
            'dateCreated'   => Helper::date($s['created'], true),
            'dateModified'  => Helper::date($s['modified'], true),

            // Site based
            'url'           => \JURI::current(),
            'siteurl'       => Helper::getSiteURL(),
            'sitename'      => Helper::getSiteName()
        ];

        // Prepare snippet data
        $data = [];
        switch ($contentType)
        {
            case 'article':
                // Article's headline should not exceed the 110 characters
                $commonData['title'] = StringHelper::substr($commonData['title'], 0, 110);

                $data = [
                    'publisherName' => $s->get('publisher_name', Helper::getSiteName()),
                    'publisherLogo' => Helper::absURL($s->get('publisher_logo', Helper::getSiteLogo()))
                ];

                break;
            case 'product':
                $data = [
                    'offerPrice'   => Helper::formatPrice($s['offerPrice']),
                    'brand'        => $s->get('brand', Helper::getSiteName()),
                    'sku'          => $s['sku'],
                    
                    // Fallback to 'sku' property to prevent structured data warning. Remove this fallback in the upcoming refactoring.
                    'mpn'          => $s->get('mpn') ? $s->get('mpn') : $s['sku'],
                    'currency'     => $s['currency'],
                    'condition'    => $s['offerItemCondition'],
                    'availability' => $s['offerAvailability'],
                    'priceValidUntil' => $s->get('priceValidUntil', '2100-12-31T10:00:00')
                ];
                
                // add reviews
                $data['review'] = $s['reviews'];
                break;
            case 'event':
                $data = [
                    'type'      => $s['type'],
                    'startdate' => Helper::date($s['startDate']),
                    'enddate'   => Helper::date($s['endDate']),
                    'location'  => ['name' => $s['locationName'], 'address' => $s['locationAddress']],
                    'performer' => ['name' => $s['performerName'], 'type' => $s['performerType']],
                    'status'    => $s['status'],
                    'offer'     => [
                        'availability'   => $s['offerAvailability'], 
                        'startDateTime'  => Helper::date($s['offerStartDate']),
                        'price'          => Helper::formatPrice($s['offerPrice']),
                        'currency'       => $s['offerCurrency'],
                        'inventoryLevel' => $s['offerInventoryLevel']
                    ]
                ];
                break;
            case 'recipe':
                $data = [
                    'prepTime'      => $s['prepTime'] ? 'PT' . $s['prepTime'] . 'M' : null,
                    'cookTime'      => $s['cookTime'] ? 'PT' . $s['cookTime'] . 'M' : null,
                    'totalTime'     => $s['totalTime'] ? 'PT' . $s['totalTime'] . 'M' : null,
                    'calories'      => $s['calories'],
                    'yield'         => $s['yield'],
                    'ingredient'    => Helper::makeArrayFromNewLine($s['ingredient']),
                    'instructions'  => Helper::makeArrayFromNewLine($s['instructions']),
                    'category'      => $s['category'],
                    'cuisine'       => $s['cuisine'],
                    'video'         => $s['video'],
                    'keywords'      => $s['keywords'],
                ];
                break;
            case 'movie':
                // genre
                $genre = $s->get('genre', '');
                $genre_ = [];

                if (!empty($genre) && is_string($genre))
                {
                    $genre = explode(',', $genre);

                    foreach ($genre as $actor)
                    {
                        $genre_[] = (object)[
                            'name' => $actor
                        ];
                    }
                } else 
                {
                    $genre_ = $genre;
                }

                // creators
                $creators = $s->get('creators', '');
                $creators_ = [];

                if (!empty($creators) && is_string($creators))
                {
                    $creators = explode(',', $creators);

                    foreach ($creators as $creator)
                    {
                        $creators_[] = (object)[
                            'name' => $creator
                        ];
                    }
                } else 
                {
                    $creators_ = $creators;
                }

                // directors
                $directors = $s->get('directors', '');
                $directors_ = [];

                if (!empty($directors) && is_string($directors))
                {
                    $directors = explode(',', $directors);

                    foreach ($directors as $director)
                    {
                        $directors_[] = (object)[
                            'name' => $director
                        ];
                    }
                } else 
                {
                    $directors_ = $directors;
                }

                // We need a better and more dynamic way to handle Repeatable Field values.
                // We can move this block to MappingOptions somehow.
                $actors = $s->get('actors', '');
                $actors_ = [];

                if (!empty($actors) && is_string($actors))
                {
                    $actors = explode(',', $actors);

                    foreach ($actors as $actor)
                    {
                        $actors_[] = (object)[
                            'name' => $actor
                        ];
                    }
                } else 
                {
                    $actors_ = $actors;
                }

                $data = [
                    'genre'              => $genre_,
                    'creators'           => $creators_,
                    'directors'          => $directors_,
                    'actors'             => $actors_,
                    'trailerUrl'         => $s['trailerUrl']
                ];

                // set duration
                if (!empty($s['duration']))
                {
                    $data['duration'] = 'PT' . $s['duration'] . 'M';
                }
                
                $data['review'] = $s['reviews'];

                break;
            case 'review':

                // We need a better and more dynamic way to handle Repeatable Field values.
                // We can move this block to MappingOptions somehow.
                $actors = $s->get('actors', '');
                $actors_ = [];

                if (!empty($actors) && is_string($actors))
                {
                    $actors = explode(',', $actors);

                    foreach ($actors as $actor)
                    {
                        $actors_[] = (object)[
                            'name' => $actor
                        ];
                    }
                } else 
                {
                    $actors_ = $actors;
                }

                $data = [
                    'itemReviewedType' => $s['itemReviewedType'],
                    'itemReviewedURL'  => $s['itemReviewedURL'],
                    'itemReviewedPublishedDate' => $s['item_reviewed_published_date'],
                    'movie_director'   => $s['item_reviewed_movie_director'],
                    'product_sku'      => $s['item_reviewed_product_sku'],
                    'product_brand'    => $s['item_reviewed_product_brand'],
                    'product_description' => $s['item_reviewed_product_description'],
                    'currency'         => $s['item_reviewed_product_currency'],
                    'condition'        => $s['item_reviewed_product_offeritemcondition'],
                    'availability'     => $s['item_reviewed_product_offeravailability'],
                    'offerprice'       => $s['item_reviewed_product_offerprice'],
                    'pricevaliduntil'  => $s['item_reviewed_product_pricevaliduntil'],
                    'book_author'      => $s['item_reviewed_book_author'],
                    'book_author_url'  => $s['item_reviewed_book_author_url'],
                    'book_isbn'        => $s['item_reviewed_book_isbn'],
                    'address'          => $s['address'],
                    'review'           => $s['reviews'],
                    'priceRange'       => $s['priceRange'],
                    'telephone'        => $s['telephone'],
                    'actors'           => $actors_,
                    'language_code'    => explode('-', \JFactory::getLanguage()->getTag())[0]
                ];
                break;
            case 'factcheck':
                switch ($s['factcheckRating']) {
                    // there is no textual representation for zero (0)
                    case '1':
                        $textRating = 'False';
                        break;
                    case '2':
                        $textRating = 'Mostly false';
                        break;
                    case '3':
                        $textRating = 'Half true';
                        break;
                    case '4':
                        $textRating = 'Mostly true';
                        break;
                    case '5':
                        $textRating = 'True';
                        break;
                    default:
                        $textRating = 'Hard to categorize';
                        break;
                }

                $data = [
                    'factcheckURL'          => ($s['multiple']) ? $commonData['url'] . $s['anchorName'] : $commonData['url'],
                    'claimAuthorType'       => $s['claimAuthorType'],
                    'claimAuthorName'       => $s['claimAuthorName'],
                    'claimURL'              => $s['claimURL'],
                    'claimDatePublished'    => $s['claimDatePublished'],
                    'factcheckRating'       => $s['factcheckRating'],
                    'bestFactcheckRating'   => ($s['factcheckRating'] != '-1') ? '5' : '-1',
                    'worstFactcheckRating'  => ($s['factcheckRating'] != '-1') ? '1' : '-1',
                    'alternateName'         => $textRating
                ];
                break;
            case 'video':
                $data = [
                    'contentUrl' => $s['contentUrl'],
                    'transcript' => $s['transcript']
                ];
                break;
            case 'custom_code':
                $data = [
                    'custom' => $s['custom_code']
                ];
                break;
            case 'jobposting':
                $data = [
                    'hiring_oprganization_name' => $s['hiring_oprganization_name'],
                    'hiring_oprganization_url' => $s['hiring_oprganization_url'],
                    'hiring_organization_logo' => Helper::absURL($s['hiring_organization_logo']),
                    'country' => $s['addressCountry'],
                    'address' => $s['streetAddress'],
                    'region' => $s['region'],
                    'locality' => $s['locality'],
                    'postal_code' => $s['postal_code'],
                    'industry' => $s['industry'],
                    'education' => $s['educationRequirements'],
                    'valid_through'  => Helper::date($s['valid_through']),
                    'employmenttype' => $s['employmenttype'],
                    'salary' => (strpos($s['salary'], '-') === false ? Helper::formatPrice($s['salary']) : explode('-', $s['salary'])),
                    'salary_currency' => $s['currency'],
                    'salary_unit' => $s['salary_unit']
                ];
                break;
            case 'faq':
                $mode = $s->get('mode', 'auto');

                $faq = $s['faq_repeater_fields'];
                
                $allowed_tags = '<h1><h2><h3><h4><h5><h6><br><ol><ul><li><p><a><div><b><strong><i><em>';
                
                $faqData = [];

                switch ($mode)
                {
                    // Manual Mode
                    case 'manual':
                        foreach ($faq as $item)
                        {
                            $question = trim($item->question);
                            $question = preg_replace('/\s\s+/', ' ', $question);
                            $question = strip_tags($question);

                            $answer = trim($item->answer);
                            $answer = strip_tags($answer, $allowed_tags);

                            $faqData[] = [
                                'question' => $question,
                                'answer'   => $answer
                            ];
                        }
                        break;
                    // Auto Mode
                    case 'auto':
                        $pageHTML = Helper::getBuffer();

                        // Default to page's text, if the document's HTML is not available yet.
                        if (empty($pageHTML))
                        {
                            $pageHTML = $s['introtext'] . $s['fulltext'];
                        }

                        $question_selector = $s->get('question_selector', '.question');
                        $answer_selector = $s->get('answer_selector', '.answer');

                        // Find questions and answers
                        $questions = Helper::findFAQContent($pageHTML, $question_selector);
                        $answers = Helper::findFAQContent($pageHTML, $answer_selector);

                        // Combine the Q&A
                        if (count($questions) && count($answers))
                        {
                            $counter = 0;
                            foreach ($questions as $q)
                            {
                                $question = trim($q['value']);

                                $answer = isset($answers[$counter]['html']) ? $answers[$counter]['html'] : '';

                                // Remove spaces, new lines, invalid HTML tags and empty paragraphs.
                                $answer = preg_replace('/\s\s+/', ' ', $answer);
                                $answer = strip_tags($answer, $allowed_tags);
                                $answer = preg_replace('/<p>\s*<\/p>/', '', $answer);
                                $answer = trim($answer);

                                $faqData[] = [
                                    'question' => $question,
                                    'answer' => $answer
                                ];

                                $counter++;
                            }
                        } else 
                        {
                            Helper::log([
                                'Error'             => 'No FAQs found',
                                'HTML to search'    => $pageHTML,
                                'Question Selector' => $question_selector,
                                'Questions Found'   => count($questions),
                                'Answer Selector'   => $answer_selector,
                                'Answers Found'     => count($answers)
                            ]);
                        }
                        break;
                }
                
                $data = [
                    'data' => $faqData
                ];
                break;
            case 'localbusiness':
                $commonData['id'] = $commonData['url'];

                $data = [
                    'type' => $s->get('type', 'LocalBusiness'),
                    'name' => $s->get('name', Helper::getSiteName()),
                    'addressCountry' => $s['addressCountry'],
                    'addressLocality' => $s['addressLocality'],
                    'streetAddress' => $s['streetAddress'],
                    'addressRegion' => $s['addressRegion'],
                    'postalCode' => $s['postalCode'],
                    'geo' => array_map('trim', explode(',', $s->get('geo', ''), 2)),
                    'telephone' => $s['telephone'],
                    'price_range' => $s['priceRange'],
                    'openingHours' => $s['openinghours'],
                    'servesCuisine' => $s['servesCuisine'],
                    'menu' => $s['menu']
                ];
                break;
            case 'course':
                $s->set('validFrom', Helper::date($s->get('validFrom')));
                $s->set('start_date', Helper::date($s->get('start_date')));
                $s->set('end_date', Helper::date($s->get('end_date')));
                
                $data = $s->toArray();
            case 'service':
                $data = [
                    'offerPrice' => Helper::formatPrice($s['offerPrice']),
                    'currency' => $s['currency'],
                    'provider_type' => $s['provider_type'],
                    'provider_name' => $s['provider_name'],
                    'provider_image' => Helper::absURL($s['provider_image']),
                    'country' => $s['provider_country'],
                    'city' => $s['provider_city'],
                    'address' => $s['provider_streetAddress'],
                    'region' => $s['provider_addressRegion'],
                    'postal_code' => $s['provider_postalCode'],
                    'phone' => $s['provider_phone'],
                ];
                break;
        }

        return array_merge($data, $commonData);
    }

    /**
     *  Get View Name
     *
     *  @return  string  Return the current executed view in the front-end
     */
    protected function getView()
    {
        return $this->app->input->get($this->thingRequestViewVar);
    }

    /**
     * Prepare given text with Content and Field plugins
     *
     * @param  string $text
     *
     * @return string 
     */
    private function prepareText($text)
    {
        $context = $this->app->input->get('option', 'com_content') . '.' . $this->getView();

        $params = new \JRegistry();

        $article = new \stdClass();
        $article->text = $text;
        $article->id   = $this->getThingID();

        \JPluginHelper::importPlugin('content', 'fields');
        $this->app->triggerEvent('onContentPrepare', [$context, &$article, &$params, 0]);

        return $article->text;
    }

    /**
     *  Log messages
     *
     *  @param   string  $message  The message to log
     *
     *  @return  void
     */
    protected function log($message)
    {
        Helper::log(\JText::_('PLG_GSD_' . $this->_name . '_ALIAS') . ' - ' . $message);
    }
}

?>
