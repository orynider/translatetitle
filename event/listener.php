<?php
/**
*
* @package Topic title
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
	private $topic_title = '';

	/** @var \phpbb\auth\auth */
	protected $auth;

	/** @var \phpbb\request\request */
	protected $request;

	/** @var \phpbb\template\template */
	protected $template;

	/** @var \phpbb\user */
	protected $user;
	
	/** @var \phpbb\language\language $language */
	protected $language;
	
	/**
	* Constructor
	*
	* @param \phpbb\auth\auth					$auth				Auth object
	* @param \phpbb\request\request			$request			Request object
	* @param \phpbb\template\template		$template			Template object
	* @param \phpbb\user								$user				User object
	* @param \phpbb\language\language		$language		Language object
	*
	* @access public
	*/
	public function __construct(
			\phpbb\auth\auth $auth,
			\phpbb\request\request $request,
			\phpbb\template\template $template,
			\phpbb\user $user,
			\phpbb\language\language $language,
			\phpbb\db\driver\driver_interface $db)
	{
		$this->auth = $auth;
		$this->request = $request;
		$this->template = $template;
		$this->user = $user;
		$this->language = $language;
		$this->db = $db;
		
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
			'core.posting_modify_submit_post_before'	=> 'topic_title_add',
			'core.posting_modify_message_text'	=> 'modify_message_text',
			'core.submit_post_modify_sql_data'	=> 'submit_post_modify_sql_data',
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
	
	//3rd topic_title = ''
	public function topic_data_topic_title($event)
	{
		$mode = $event['mode'];
		$post_data = $event['post_data'];
		$page_data = $event['page_data'];
		$this->user->add_lang_ext('orynider/translatetitle', 'common');
		$post_data['topic_title'] = (null !== $this->user->lang($post_data['topic_title'])) ? $this->user->lang($post_data['topic_title']) : $post_data['topic_title'];
		if ($this->auth->acl_get('f_topic_title', $event['forum_id']) && ($mode == 'post' || ($mode == 'edit' && $post_data['topic_first_post_id'] == $post_data['post_id'])))
		{
			$page_data['topic_title'] = $this->request->variable('topic_title', $post_data['topic_title'], true);
			$page_data['topic_title'] = (null !== $this->user->lang($page_data['topic_title'])) ? $this->user->lang($page_data['topic_title']) : $page_data['topic_title'];
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

	public function topic_title_add($event)
	{
		/*
		$event['data'] = array_merge($event['data'], array(
			'topic_title'	=> $event['post_data']['topic_title'],
		));
		*/
	}

	//First
	public function modify_message_text($event)
	{
		$this->user->add_lang_ext('orynider/translatetitle', 'common');
		$event['post_data']['topic_title'] = (null !== $this->user->lang($event['post_data']['topic_title'])) ? $this->user->lang($event['post_data']['topic_title']) : censor_text($event['post_data']['topic_title']);
		$event['post_data'] = array_merge($event['post_data'], array(
			'topic_title'	=> $this->request->variable('topic_title', $event['post_data']['topic_title'], true),
		));
	}

	public function submit_post_modify_sql_data($event)
	{
		/*
		$mode = $event['post_mode'];
		$topic_title = $event['data']['topic_title'];
		$data_sql = $event['sql_data'];
		if (in_array($mode, array('post', 'edit_topic', 'edit_first_post')))
		{
			$data_sql[topics_table]['sql']['topic_title'] = $topic_title;
		}
		$event['sql_data'] = $data_sql;
		*/
	}

	public function topic_title_add_viewtopic($event)
	{
		$this->user->add_lang_ext('orynider/translatetitle', 'common');
		$topic_data = $event['topic_data'];
		$topic_data['topic_title']= (null !== $this->user->lang($topic_data['topic_title'])) ? $this->user->lang($topic_data['topic_title']) : censor_text($topic_data['topic_title']);
		$this->template->assign_var('TOPIC_TITLE', $topic_data['topic_title']);
	}
	
	public function assign_template_vars_before($event)
	{
		$this->user->add_lang_ext('orynider/translatetitle', 'common');
		$topic_data = $event['topic_data'];
		$topic_data['topic_title']= (null !== $this->user->lang($topic_data['topic_title'])) ? $this->user->lang($topic_data['topic_title']) : censor_text($topic_data['topic_title']);
		$this->template->assign_var('TOPIC_TITLE', $topic_data['topic_title']);
		$event['topic_data'] = $topic_data;	
	}
	
	public function modify_topics_data($event)
	{
		$rowset = $event['rowset'];
		$topic_list = $event['topic_list'];
		
		foreach ($topic_list as $topic_id)
		{
			$row = &$rowset[$topic_id];
			$this->template->assign_var('TOPIC_TITLE', $row['topic_title']);
			
			$row['topic_title'] = !empty($this->user->lang($row['topic_title'])) ?$this->user->lang($row['topic_title']) : censor_text($row['topic_title']);
			//$this->template->assign_block_vars('topicrow', $row);
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
			$this->user->add_lang_ext('orynider/translatetitle', 'common');
			
			$topic_row = $event['topic_row'];
			$topic_row['topic_title'] = !empty($this->user->lang($row['topic_title'])) ? $this->user->lang($row['topic_title']) : censor_text($row['topic_title']);
			//$this->template->assign_block_vars('topicrow', $topic_row);
			$this->template->assign_var('TOPIC_TITLE', $topic_row['topic_title']);
			$event['row'] = $topic_row;
		}
	}
	
	public function display_forums_modify_category_template_vars($event)
	{
		$row = $event['row'];
		if (!empty($row['forum_last_post_subject']))
		{
			$this->user->add_lang_ext('orynider/translatetitle', 'common');
			
			$row['forum_last_post_subject'] = !empty($this->user->lang($row['forum_last_post_subject'])) ? $this->user->lang($row['forum_last_post_subject']) : censor_text($row['forum_last_post_subject']);
			
			//$this->template->assign_var('LAST_POST_SUBJECT', $row['forum_last_post_subject']);
			$event['row'] = $row;
		}
	}
	
	public function display_forums_modify_template_vars($event)
	{
		$row = $event['row'];
		if (!empty($row['forum_last_post_subject']))
		{
			$this->user->add_lang_ext('orynider/translatetitle', 'common');	
			
			$last_post_subject = $row['forum_last_post_subject'] = !empty($this->user->lang($row['forum_last_post_subject'])) ? $this->user->lang($row['forum_last_post_subject']) : censor_text($row['forum_last_post_subject']);
			
			// Create last post link information, if appropriate
			if ($row['forum_last_post_id'])
			{
				$last_post_subject_truncated = truncate_string($last_post_subject, 30, 255, false, $this->user->lang['ELLIPSIS']);
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
			$this->user->add_lang_ext('orynider/translatetitle', 'common');
			
			$topic_row = $event['topic_row'];
			$row['topic_title'] = $topic_row['topic_title'] = !empty($this->user->lang($row['topic_title'])) ? $this->user->lang($row['topic_title']) : censor_text($row['topic_title']);
			$event['topic_row'] = array_merge($event['topic_row'], array(
				'TOPIC_TITLE'	=> $topic_row['topic_title'],
			));		
		}
		$event['row'] = $row;
	}
	
	public function mcp_view_forum_modify_sql($event)
	{
		$sql = $event['sql'];
		$topics_per_page= $event['topics_per_page'];
		$start = $event['start'];
			
		$result = $this->db->sql_query_limit($sql, $topics_per_page, $start);
		
		$this->user->add_lang_ext('orynider/translatetitle', 'common');
		
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
		
			$row_ary['topic_title'] = $topic_row['topic_title'] = !empty($this->user->lang($row_ary['topic_title'])) ? $this->user->lang($row_ary['topic_title']) : censor_text($row_ary['topic_title']);
				
			$this->template->assign_var('TOPIC_TITLE', $topic_row['topic_title']);
			//$this->template->assign_block_vars('topicrow', $topic_row);
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
			$this->user->add_lang_ext('orynider/translatetitle', 'common');
			
			$row['post_subject'] = !empty($this->user->lang($row['post_subject'])) ? $this->user->lang($row['post_subject']) : censor_text($row['post_subject']);
			//$row['post_text'] = !empty($this->user->lang($row['post_text'])) ? $this->user->lang($row['post_text']) : censor_text($row['post_text']);
			$this->template->assign_var('POST_SUBJECT', $row['post_subject']);
			//$this->template->assign_var('MESSAGE', $row['post_subject']);
			$event['row'] = $row;
		}
	}
	
	public function search_modify_tpl_ary($event)
	{
		$row = $event['row'];
		if ($event['show_results'] == 'topics' && !empty($row['topic_title']))
		{
			$this->user->add_lang_ext('orynider/translatetitle', 'common');
			$tpl_array = $event['tpl_ary'];
			$tpl_array['topic_title'] = !empty($this->user->lang($row['topic_title'])) ? $this->user->lang($row['topic_title']) : censor_text($row['topic_title']);
			$event['tpl_ary'] = $tpl_array;
		}
	}
}

