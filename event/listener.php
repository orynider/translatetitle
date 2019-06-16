<?php
/**
*
* @package Translate Topic title
* @copyright (c) 2016 orynider
* @license http://opensource.org/licenses/gpl-2.0.php GNU General Public License v2
*
*
*/

namespace orynider\translatetitle\event;

/**
* @ignore
*/
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
* Event listener
*/
class listener implements EventSubscriberInterface
{

	/** @var \phpbb\auth\auth */
	protected $auth;

	/** @var \phpbb\request\request */
	protected $request;

	/** @var \phpbb\template\template */
	protected $template;

	/** @var \phpbb\config\config */ 
	protected $config;

	/** @var \phpbb\user */
	protected $user;

	/** @var \phpbb\language\language $language */
	protected $language;

	/** @var \phpbb\db\driver\driver_interface $db */			
	protected $db;

	/**
	* Name (including vendor) of the extension
	* @var string
	*/
	protected $ext_name;

	/**
	* Constructor
	*
	* @param \phpbb\auth\auth							$auth				Auth object
	* @param \phpbb\request\request					$request			Request object
	* @param \phpbb\config\config 						$config				Configuration object
	* @param \phpbb\template\template				$template			Template object
	* @param \phpbb\user										$user				User object
	* @param \phpbb\language\language				$language		Language object
	* @param \phpbb\db\driver\driver_interface 	$db 					Database object
	*
	* @access public
	*/
	public function __construct(
			\phpbb\auth\auth $auth,
			\phpbb\request\request $request,
			\phpbb\config\config $config,
			\phpbb\template\template $template,
			\phpbb\user $user,
			\phpbb\language\language $language,
			\phpbb\db\driver\driver_interface $db)
	{
		$this->auth = $auth;
		$this->request = $request;
		$this->config = $config;
		$this->template = $template;
		$this->user = $user;
		$this->language = $language;
		$this->db = $db;
		$this->ext_name = $request->variable('ext_name', 'orynider/translatetitle');
		
	}

	/**
	* Assign functions defined in this class to event listeners in the core
	*
	* @return array
	* @static
	* @access public
	*/
	static public function getSubscribedEvents()
	{
		return array(
			'core.permissions'	=> 'add_permission',
			'core.posting_modify_template_vars'	=> 'topic_data_topic_title',
			'core.posting_modify_submission_errors'	=> 'topic_title_add_to_post_data',
			'core.viewtopic_modify_page_title'		=> 'topic_title_add_viewtopic',
			'core.viewtopic_assign_template_vars_before'	=> 'assign_template_vars_before',
			'core.viewtopic_modify_post_action_conditions'	=> 'modify_post_action_conditions',
			'core.viewforum_modify_topics_data'	=> 'modify_topics_data',
			'core.viewforum_modify_topicrow'		=> 'modify_topicrow',
			'core.search_modify_tpl_ary'	=> 'search_modify_tpl_ary',
			'core.mcp_view_forum_modify_topicrow'	=> 'mcp_view_forum_modify_topicrow',
			'core.display_forums_modify_category_template_vars'	=> 'display_forums_modify_category_template_vars',
			'core.display_forums_modify_template_vars'	=> 'display_forums_modify_template_vars',
		);
	}

	/**
	* Add administrative permissions to manage forums
	*
	* @param object $event The event object
	* @return null
	* @access public
	*/
	public function add_permission($event)
	{
		$permissions = $event['permissions'];
		$permissions['f_topic_title'] = array('lang' => 'ACL_F_TOPIC_TITLE', 'cat' => 'post');
		$event['permissions'] = $permissions;
		
	}

	/**
	 * Load common language file during user setup
	 *
	 * @param	\phpbb\event\data	$event	The event object
	 * @return	void
	 */
	public function load_language_on_setup($event)
	{
		$lang_set_ext = $event['lang_set_ext'];
		$lang_set_ext[] = array(
			'ext_name' => $this->ext_name,
			'lang_set' => 'common',
		);
		$event['lang_set_ext'] = $lang_set_ext;
	}

	//3rd topic_title = ''
	public function topic_data_topic_title($event)
	{
		$this->user->add_lang_ext($this->ext_name, 'common');
		
		$mode = $event['mode'];
		$post_data = $event['post_data'];
		$page_data = $event['page_data'];
		$post_data['topic_title'] = (true === $this->language->is_set($post_data['topic_title'])) ? $this->language->lang($post_data['topic_title']) : $post_data['topic_title'];
		if ($this->auth->acl_get('f_topic_title', $event['forum_id']) && ($mode == 'post' || ($mode == 'edit' && $post_data['topic_first_post_id'] == $post_data['post_id'])))
		{
			$page_data['topic_title'] = $this->request->variable('topic_title', $post_data['topic_title'], true);
			$page_data['topic_title'] = (true === $this->language->is_set($page_data['topic_title'])) ? $this->language->lang($page_data['topic_title']) : $page_data['topic_title'];
			$page_data['S_TOPIC_TITLE'] = true;
		}
		$event['page_data']	= $page_data;
	}

	//Second topic_title = ''
	public function topic_title_add_to_post_data($event)
	{
		if ($this->auth->acl_get('f_topic_title', $event['forum_id']))
		{
			$event['post_data'] = array_merge($event['post_data'], array(
				'topic_title'	=> $this->request->variable('topic_title', '', true),
			));
		}
	}

	public function topic_title_add_viewtopic($event)
	{
		$this->user->add_lang_ext($this->ext_name, 'common');
		$topic_data = $event['topic_data'];
		$topic_data['topic_title']= (true === $this->language->is_set($topic_data['topic_title'])) ? $this->language->lang($topic_data['topic_title']) : censor_text($topic_data['topic_title']);
		$this->template->assign_var('TOPIC_TITLE', $topic_data['topic_title']);
	}

	public function assign_template_vars_before($event)
	{
		$this->user->add_lang_ext($this->ext_name, 'common');
		$topic_data = $event['topic_data'];
		$topic_data['topic_title']= (true === $this->language->is_set($topic_data['topic_title'])) ? $this->language->lang($topic_data['topic_title']) : censor_text($topic_data['topic_title']);
		$this->template->assign_var('TOPIC_TITLE', $topic_data['topic_title']);
		$event['topic_data'] = $topic_data;
	}

	public function modify_topics_data($event)
	{
		$rowset = $event['rowset'];
		$topic_list = $event['topic_list'];
		
		foreach ($topic_list as $topic_id)
		{
			$row = $rowset[$topic_id];
			$this->user->add_lang_ext($this->ext_name, 'common');
			$row['topic_title'] = (true === $this->language->is_set($row['topic_title'])) ? $this->language->lang($row['topic_title']) : censor_text($row['topic_title']);
			$this->template->assign_var('TOPIC_TITLE', $row['topic_title']);
			$rowset[$topic_id] = $row;
		}
		$event['rowset'] = $rowset;
	}

	public function modify_topicrow($event)
	{
		$row = $event['row'];
		if (!empty($row['topic_title']))
		{
			$this->user->add_lang_ext($this->ext_name, 'common');
			
			$row['topic_title']= (true === $this->language->is_set($row['topic_title'])) ? $this->language->lang($row['topic_title']) : censor_text($row['topic_title']);
			
			$event['row'] = array_merge($event['row'], array(
				'TOPIC_TITLE'	=> $row['topic_title'],
			));	
		}
		$event['row'] = $row;
		
		$topic_row = $event['topic_row'];
		if (!empty($topic_row['topic_title']))
		{
			$this->user->add_lang_ext($this->ext_name, 'common');
			
			$topic_row['topic_title'] = (true === $this->language->is_set($topic_row['topic_title'])) ? $this->language->lang($topic_row['topic_title']) : censor_text($topic_row['topic_title']);
			$event['topic_row'] = array_merge($event['topic_row'], array(
				'TOPIC_TITLE'	=> $topic_row['topic_title'],
			));	
		}
		$event['topic_row'] = $topic_row;
	}

	public function display_forums_modify_category_template_vars($event)
	{
		$row = $event['row'];
		if (!empty($row['forum_last_post_subject']))
		{
			$this->user->add_lang_ext($this->ext_name, 'common');
			
			$row['forum_last_post_subject'] = ($this->language->lang($row['forum_last_post_subject'])) ? $this->language->lang($row['forum_last_post_subject']) : censor_text($row['forum_last_post_subject']);
			$event['row'] = $row;
		}
	}

	public function display_forums_modify_template_vars($event)
	{
		$row = $event['row'];
		if (!empty($row['forum_last_post_subject']))
		{
			$this->user->add_lang_ext($this->ext_name, 'common');
			
			$last_post_subject = $row['forum_last_post_subject'] = ($this->language->lang($row['forum_last_post_subject'])) ? $this->language->lang($row['forum_last_post_subject']) : censor_text($row['forum_last_post_subject']);
			
			// Create last post link information, if appropriate
			if ($row['forum_last_post_id'])
			{
				$last_post_subject_truncated = truncate_string($last_post_subject, 30, 255, false, $this->language->lang('ELLIPSIS'));
			}
			else
			{
				$last_post_subject_truncated = '';
			}
			$event['forum_row'] = array_merge($event['forum_row'], array(
				'LAST_POST_SUBJECT'	=> $last_post_subject,
				'LAST_POST_SUBJECT_TRUNCATED'	=> $last_post_subject_truncated,
			));	
		}
		$event['row'] = $row;
	}

	public function mcp_view_forum_modify_topicrow($event)
	{
		$row = $event['row'];
		if (!empty($row['topic_title']))
		{
			$this->user->add_lang_ext($this->ext_name, 'common');
			
			$row['topic_title']= (true === $this->language->is_set($row['topic_title'])) ? $this->language->lang($row['topic_title']) : censor_text($row['topic_title']);
			
			$event['row'] = array_merge($event['row'], array(
				'TOPIC_TITLE'	=> $row['topic_title'],
			));	
		}
		$event['row'] = $row;
		
		$topic_row = $event['topic_row'];
		if (!empty($topic_row['topic_title']))
		{
			$this->user->add_lang_ext($this->ext_name, 'common');
			
			$topic_row['topic_title'] = (true === $this->language->is_set($topic_row['topic_title'])) ? $this->language->lang($topic_row['topic_title']) : censor_text($topic_row['topic_title']);
			$event['topic_row'] = array_merge($event['topic_row'], array(
				'TOPIC_TITLE'	=> $topic_row['topic_title'],
			));	
		}
		$event['topic_row'] = $topic_row;
	}

	public function mcp_view_forum_modify_sql($event)
	{
		if ($this->config['load_db_lastread'])
		{
			$read_tracking_join = ' LEFT JOIN ' . FORUMS_TRACK_TABLE . ' ft ON (ft.user_id = ' . (int) $this->user->data['user_id'] . '
				AND ft.forum_id = f.forum_id)';
			$read_tracking_select = ', ft.mark_time';
		}
		else
		{
			$read_tracking_join = $read_tracking_select = '';
		}
		
		$sql = $event['sql'];
		$topics_per_page= $event['topics_per_page'];
		$start = $event['start'];
			
		$result = $this->db->sql_query_limit($sql, $topics_per_page, $start);
		
		$this->user->add_lang_ext($this->ext_name, 'common');
		
		$topic_list = $topic_tracking_info = array();
		while ($row_ary = $this->db->sql_fetchrow($result))
		{
			$topic_list[] = $row_ary['topic_id'];
		}
		$this->db->sql_freeresult($result);
		
		$sql = "SELECT t.*$read_tracking_select
			FROM " . TOPICS_TABLE . " t $read_tracking_join
			WHERE " . $this->db->sql_in_set('t.topic_id', $topic_list, false, true);
		$result = $this->db->sql_query($sql);
		while ($row_ary = $this->db->sql_fetchrow($result))
		{
			$topic_rows[$row_ary['topic_id']] = $row_ary;
		}
		$this->db->sql_freeresult($result);
		foreach ($topic_list as $topic_id)
		{
			$row_ary = &$topic_rows[$topic_id];
		
			$row_ary['topic_title'] = $topic_row['topic_title'] = ($this->language->lang($row_ary['topic_title'])) ? $this->language->lang($row_ary['topic_title']) : censor_text($row_ary['topic_title']);
			
			$this->template->assign_var('TOPIC_TITLE', $topic_row['topic_title']);
			
			$event['topic_row'] = array_merge($event['topic_row'], array(
				'topic_title'	=> $this->request->variable('topic_title', $event['topic_row']['topic_title'], true),
			));	
			$event['topic_row'] = $topic_row;
		}
		
		$event['row'] = $row_ary;
	}

	public function modify_post_action_conditions($event)
	{
		$row = $event['row'];
		if (!empty($row['post_subject']))
		{
			$this->user->add_lang_ext($this->ext_name, 'common');
			
			$row['post_subject'] = ($this->language->lang($row['post_subject'])) ? $this->language->lang($row['post_subject']) : censor_text($row['post_subject']);
			$this->template->assign_var('POST_SUBJECT', $row['post_subject']);
			
			$event['row'] = $row;
		}
	}

	public function search_modify_tpl_ary($event)
	{
		$row = $event['row'];
		if ($event['show_results'] == 'topics' && !empty($row['topic_title']))
		{
			$this->user->add_lang_ext($this->ext_name, 'common');
			$tpl_array = $event['tpl_ary'];
			$tpl_array['topic_title'] = ($this->language->lang($row['topic_title'])) ? $this->language->lang($row['topic_title']) : censor_text($row['topic_title']);
			$event['tpl_ary'] = $tpl_array;
		}
	}

}
