<?php

/**
 * @author          Tassos Marinos <info@tassos.gr>
 * @link            http://www.tassos.gr
 * @copyright       Copyright © 2018 Tassos Marinos All Rights Reserved
 * @license         GNU GPLv3 <http://www.gnu.org/licenses/gpl.html> or later
 */

namespace NRFramework;

defined('_JEXEC') or die;

jimport('joomla.filesystem.file');

/**
 *  Novarain Framework Assignments Helper Class
 */
class Assignments
{
    /**
	 *  Assignment Type Aliases
	 *
	 *  @var  array
	 */
	public $typeAliases = array(
		'device|devices'                     => 'Device',
		'urls|url'                           => 'URL',
		'os'			                     => 'OS',
		'browsers|browser'		             => 'Browser',
		'referrer'                           => 'Referrer',
		'lang|language|languages'            => 'Language',
		'php'                                => 'PHP',
		'timeonsite'                         => 'TimeOnSite',
		'usergroups|usergroup|user_groups'   => 'GroupLevel',
		'pageviews|user_pageviews'           => 'Pageviews',
		'user_id|userid'		             => 'UserID',
		'menu'                               => 'Menu',
        'datetime|daterange|date'            => 'Date',
        'days|day'                           => 'Day',
        'months|month'                       => 'Month',
		'timerange|time'                     => 'Time',
        'acymailing'                         => 'AcyMailing',
        'akeebasubs'                         => 'AkeebaSubs',
        'components|component'	             => 'Component',
        'convertforms'	                     => 'ConvertForms',
        'geo_country|country|countries'	     => 'Country',
        'geo_continent|continent|continents' => 'Continent',
        'geo_city|city|cities'               => 'City',
        'geo_region|region|regions'          => 'Region',
        'cookiename|cookie'                  => 'Cookie',
        'ip_addresses|iprange|ip'            => 'IP',
        'k2_items|k2item'                    => 'Component\K2Item',
        'k2_cats|k2category'                 => 'Component\K2Category',
        'k2_tags|k2tag'                      => 'Component\K2Tag',
        'k2_pagetypes'                       => 'Component\K2Pagetype',
        'contentcats|category'               => 'Component\ContentCategory',
        'contentarticles|article'            => 'Component\ContentArticle',
        'eventbookingsingle'                 => 'Component\EventBookingSingle',
        'eventbookingcategory'               => 'Component\EventBookingCategory',
        'j2storesingle'                      => 'Component\J2StoreSingle',
        'j2storecategory'                    => 'Component\J2StoreCategory',
        'hikashopsingle'                     => 'Component\HikashopSingle',
        'hikashopcategory'                   => 'Component\HikashopCategory',
        'sppagebuildersingle'                => 'Component\SPPageBuilderSingle',
        'sppagebuildercategory'              => 'Component\SPPageBuilderCategory',
        'virtuemartcategory'                 => 'Component\VirtueMartCategory',
        'virtuemartsingle'                   => 'Component\VirtueMartSingle',
        'jshoppingsingle'                    => 'Component\JShoppingSingle',
        'jshoppingcategory'                  => 'Component\JShoppingCategory',
        'rsblogsingle'                       => 'Component\RSBlogSingle',
        'rsblogcategory'                     => 'Component\RSBlogCategory',
        'easyblogcategory'                   => 'Component\EasyBlogCategory',
        'easyblogsingle'                     => 'Component\EasyBlogSingle',
        'zoosingle'                          => 'Component\ZooSingle',
        'zoocategory'                        => 'Component\ZooCategory',
        'eshopcategory'                      => 'Component\EshopCategory',
        'eshopsingle'                        => 'Component\EshopSingle',
        'djcatalog2category'                 => 'Component\DJCatalog2Category',
        'djcatalog2single'                   => 'Component\DJCatalog2Single',
        'quixsingle'                         => 'Component\QuixSingle',
        'djclassifiedssingle'                => 'Component\DJClassifiedsSingle',
        'djclassifiedscategory'              => 'Component\DJClassifiedsCategory',
        'sobiprocategory'                    => 'Component\SobiProCategory',
        'sobiprosingle'                      => 'Component\SobiProSingle',
        'djeventscategory'                   => 'Component\DJEventsCategory',
        'djeventssingle'                     => 'Component\DJEventsSingle',
        'jcalprocategory'                    => 'Component\JCalProCategory',
        'jcalprosingle'                      => 'Component\JCalProSingle'
    );

    /**
     *  Factory object 
     * 
     *  @var \NRFramework\Factory
     */
    protected $factory;

    /**
     *  ctor
     */
    public function __construct($factory = null)
    {
        if (!$factory)
        {
            $factory = new \NRFramework\Factory();
        }

        $this->factory = $factory;        
    }
    
    /**
	 *  Check all Assignments
	 *
	 *  @param   array|object   $assignments_info   Array/Object containing assignment info
	 *  @param   string         $match_method       The matching method (and|or) - Deprecated
	 *  @param   bool           $debug              Set to true to request additional debug information about assignments
     * 
	 *  @return  bool|array                         True if check passes. If $debug is set to true an array will be returned with
     *                                              the result in the first element and debug info in the second.
	 */
	public function passAll($assignments_info, $match_method = 'and', $debug = false)
	{
        if (!$assignments_info)
        {
            return true;
        }
        
        // convert $assignments_info parameter from object (used by existing extensions) to array
        if (is_object($assignments_info))
        {
            $assignments_info = $this->prepareAssignmentsFromObject($assignments_info, $match_method);
        }
        
        // prepare assignment data
        $assignments = $this->prepareAssignments($assignments_info);

        $debug_info = [];
        if ($debug)
        {
            $debug_info = $this->generateDebugInfo($assignments);
        }

        // return true if no assignments are given
        if (empty($assignments))
        {
            return $debug ? [true, $debug_info] : true;
        }

        $pass = false;

        foreach ($assignments as $group)
        {
            // Pass all assignments in the group
            if ($pass = $this->passAnd($group))
            {
                break;
            }
        }

        return $debug ? [$pass, $debug_info] : $pass;
    }

    /**
     * Check if all of the given assignments passes the check
     *
     * @param   array   $assignments       The assignments array to check
     *
     * @return  bool
     */
    private function passAnd($assignments)
    {
        if (!is_array($assignments) || count($assignments) == 0)
        {
            return;
        }

        foreach ($assignments as $assignment)
        {
            if (is_null($assignment) || !\property_exists($assignment, 'class') || is_null($assignment->class))
            {
                return;
            }

            $assignmentInstance = new $assignment->class($assignment->options, $this->factory);
            $pass = $this->passStateCheck($assignmentInstance->pass(), $assignment->options->assignment_state);

            // Fail if any of the assignments doesn't pass the check.
            if (!$pass)
            {
                return false;
            }
        }

        return true;
    }
   
    /**
     *  Checks if an assignment exists
     *
     *  @param  string $assignment Assignment class name or alias
     *  @return bool
     */
    public function exists($assignment)
    {
        if (!$assignment)
        {
            return false;
        }
        $assignment = strtolower($assignment);

        // search by Assignment name
        if (array_search($assignment, $this->typeAliases) !== false)
        {
            return true;
        }

        // search assignment aliases
        foreach (array_keys($this->typeAliases) as $key)
        {
            if (strpos($key, $assignment) !== false)
            {
                return true;
            }
        }
        return false;
    }

    /**
     *  Returns the classname for a given assignment alias
     *
     *  @param  string       $alias
     *  @return string|void
     */
    public function aliasToClassname($alias)
    {
        $alias = strtolower($alias);
        foreach ($this->typeAliases as $aliases => $type)
        {
            if (strtolower($type) == $alias)
            {
                return $type;
            }

            $aliases = explode('|', strtolower($aliases));
            if (in_array($alias, $aliases))
            {
                return $type;                
            }   
        }

        return null;
    }

    /**
    *  Assignment pass check based on the assignment state
    *
    *  @param   boolean  $pass        
    *  @param   string   $assignment_state  The assignment state
    *
    *  @return  boolean
    */
    private function passStateCheck($pass = true, $assignment_state = null)
    {
        $assignment_state = $assignment_state ?: $this->assignment;
        return $pass ? ($assignment_state == 'include') : ($assignment_state == 'exclude');
    }

    /**
     *  Checks and prepares the given array of assignment information
     * 
     *  @param   array $assignments_info
     *  @return  array
     */
    protected function prepareAssignments($assignments_info)
    {
        $assignments = [];
        foreach ($assignments_info as $group)
        {
            if (empty($group)) 
            {
                continue;
            }

            $newGroup = [];

            foreach ($group as $a)
            {
                // check if the object has the required properties
                if (!is_object($a) ||!isset($a->alias) || !isset($a->value) || !isset($a->assignment_state))
                {
                    continue;
                }

                $assignment = new \stdClass();
                
                // check if the assignment type exists
                if (!$this->exists($a->alias) || !$this->setTypeParams($assignment, $this->aliasToClassname($a->alias)))
                {
                    $assignment->class = null;
                }

                $assignment->options = (object) array(
                    'alias'             => $a->alias,
                    'selection'         => $a->value,
                    'params'            => isset($a->params) ? $a->params : new \stdClass(),
                    'assignment_state'  => $this->getAssignmentState($a->assignment_state)
                );

                $newGroup[] = $assignment;
            }

            $assignments[] = $newGroup;
        }

        return $assignments;
    }

    /**
     *  Converts an object of assignment information to an array of groups
     *  Used by existing extensions
     * 
     *  @param  object $assignments_info
     *  @param  string $matching_method
     * 
     *  @return array of objects
     */
    public function prepareAssignmentsFromObject($assignments_info, $match_method)
    {
        if (!isset($assignments_info->params))
        {
            return [];
        }

        $params = json_decode($assignments_info->params);

        if (!is_object($params))
		{
			return [];
        }

        $assignments_info = [];
        
        foreach ($this->typeAliases as $aliases => $type)
        {
            $aliases = explode('|', $aliases);

            foreach ($aliases as $alias)
            {
                if (!isset($params->{'assign_' . $alias}) || !$params->{'assign_' . $alias})
                {
                    continue;
                }

                // Discover assignment params
                $assignment_params = new \stdClass();
                foreach ($params as $key => $value)
                {
                    if (strpos($key, "assign_" . $alias . "_param") !== false)
                    {
                        $key = str_replace("assign_" . $alias . "_param_", "", $key);
                        $assignment_params->$key = $value;
                    }
                }

                $assignments_info[] = (object) array(
                    'alias'             => $alias,
                    'assignment_state'  => $this->getAssignmentState($params->{'assign_' . $alias}),
                    'value'             => isset($params->{'assign_' . $alias . '_list'}) ? $params->{'assign_' . $alias . '_list'} : [],
                    'params'            => $assignment_params
                );
            }
        }

        if ($match_method === 'or')
        {
            // each assignemnt belongs to a separate group
            $res = [];
            foreach($assignments_info as $assignment)
            {
                $res[] = [$assignment];
            }
            return $res;
        }
        else 
        {
            // every assignment belongs to the same group
            return [$assignments_info];
        }
    }

    /**
	 *  Returns assignment's state by ID
	 *  1: Include
	 *  2: Exclude
	 *  3, -1: None
	 *
	 *  @param   integer  $state_id     Assignment's state ID
	 *
	 *  @return  string                 Assignment's state name
	 */
	private function getAssignmentState($state_id)
	{
		switch ($state_id)
		{
			case 1:
			case 'include':
				$assignment_state = 'include';
				break;
			case 2:
			case 'exclude':
				$assignment_state = 'exclude';
				break;
			case 3:
			case -1:
			case 'none':
				$assignment_state = 'none';
				break;
			default:
				$assignment_state = 'all';
				break;
		}

		return $assignment_state;
    }
    
    /**
	 *  Sets proper assignment class and method name
	 *
	 *  @param   object  &$assignment  The assignment object
	 *  @param   string  $type         The assignment type
	 *
	 *  @return  bool                   True if the class and method exist, false otherwise 
	 */
	public function setTypeParams(&$assignment, $type = '')
	{
        $class  = __NAMESPACE__ . '\\Assignments\\' . $type;
        if (!class_exists($class))
        {
            return false;
        }
        
        $assignment->class  = $class;

        return true;
    }
    
    /**
     *  Checks assignments and returns debug information
     * 
     *  @param  array $assignments
     * 
     *  @return array 
     */
    protected function generateDebugInfo($assignments)
    {
        $debug_info = [];
        foreach ($assignments as $group)
        {
            $debugGroup = [];
            foreach($group as $assignment)
            {
                if (!property_exists($assignment, 'class') || is_null($assignment->class))
                {
                    $assignment->pass = null;
                    $assignment->name = 'Unknown Assignment';
                }
                else
                {
                    $inst   = new $assignment->class($assignment->options, $this->factory);
                    $passed = $inst->pass();
                    $assignment->pass = $this->passStateCheck(
                        $passed,
                        $assignment->options->assignment_state
                    );
                    $assignment->value  = $inst->value();
                    $assignment->name   = \preg_replace('/.*\\\\(.*)$/', "$1", $assignment->class);
                }
                $debugGroup[] = $assignment;
            }
            $debug_info[] = $debugGroup;
        }

        return $debug_info;
    }
}
