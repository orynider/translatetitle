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

	/**
	* Constructor
	*
	* @param \phpbb\auth\auth				$auth				Auth object
	* @param \phpbb\request\request		$request			Request object
	* @param \phpbb\template\template	$template			Template object
	* @param \phpbb\user							$user				User object
	* @access public
	*/
	public function __construct(
			\phpbb\auth\auth $auth,
			\phpbb\request\request $request,
			\phpbb\template\template $template,
			\phpbb\user $user)
	{
		$this->auth = $auth;
		$this->request = $request;
		$this->template = $template;
		$this->user = $user;
		
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
			'core.viewforum_modify_topicrow'		=> 'modify_topicrow',
			'core.search_modify_tpl_ary'	=> 'search_modify_tpl_ary',
			'core.mcp_view_forum_modify_topicrow'	=> 'modify_topicrow',
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
		$topic_data = $event['topic_data'];
		$topic_data['topic_title']= (null !== $this->user->lang($topic_data['topic_title'])) ? $this->user->lang($topic_data['topic_title']) : censor_text($topic_data['topic_title']);
		$this->template->assign_var('TOPIC_TITLE', $topic_data['topic_title']);
	}

	public function modify_topicrow($event)
	{
		$row = $event['row'];
		if (!empty($row['topic_title']))
		{
			$topic_row = $event['topic_row'];
			$topic_row['topic_title'] = censor_text($row['topic_title']);
			$event['topic_row'] = $topic_row;
		}
	}

	public function search_modify_tpl_ary($event)
	{
		$row = $event['row'];
		if ($event['show_results'] == 'topics' && !empty($row['topic_title']))
		{
			$tpl_array = $event['tpl_ary'];
			$tpl_array['topic_title'] = censor_text($row['topic_title']);
			$event['tpl_ary'] = $tpl_array;
		}
	}
}
