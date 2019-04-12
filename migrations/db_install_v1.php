<?php
/**
*
* @package Topic title
* @copyright (c) 2016 FlorinCB (orynider)
* @license http://opensource.org/licenses/gpl-2.0.php GNU General Public License v2
*
*/

namespace orynider\translatetitle\migrations;

class v1 extends \phpbb\db\migration\migration
{
	static public function depends_on()
	{
		return array('\phpbb\db\migration\data\v310\dev');
	}

	/*
	public function update_schema()
	{
		return array(
			'change_columns'    => array(
				$this->table_prefix . 'topics'        => array(
					'topic_title'	=> array('text_uni', null),
				),
			),
		);
	}
	*/
	
	public function update_data()
	{
		return array(
			// Add permission
			array('permission.add', array('f_topic_title', false)),
			// Set permissions,
			array('permission.permission_set', array('ROLE_FORUM_FULL', 'f_topic_title', 'role')),
			array('permission.permission_set', array('ROLE_FORUM_STANDARD', 'f_topic_title', 'role')),
			array('permission.permission_set', array('ROLE_FORUM_POLLS', 'f_topic_title', 'role')),
		);
	}
}
