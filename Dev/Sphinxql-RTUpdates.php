<?php

/**
 * Simple Machines Forum (SMF)
 *
 * @package SMF
 * @author Simple Machines http://www.simplemachines.org
 * @copyright 2015 Simple Machines and individual contributors
 * @license http://www.simplemachines.org/about/smf/license.php BSD
 *
 * The Following fixes a bug in SMF to show this in the settings section.
 * SearchAPI-Sphinxql.php
 * @version 2.0.12
 */

if (!defined('SMF'))
	die('Hacking attempt...');

/**
 * Our Search API class
 * This Class should not load, it is used to store possible changes to the API
 * These are not included for lack of testing or lack of Sphinx support
 * At this time it includes functions which Sphinx only supports with RT indexes
 * We do not make use of RT indexes at this time.
 * @package SearchAPI
 */
class sphinxql_search_rtIndexes extends search_api
{
	/**
	 * Callback when a post is created
	 * @see createPost()
	 *
	 * @access public
	 * @param array $msgOptions An array of post data
	 * @param array $topicOptions An array of topic data
	 * @param array $posterOptions An array of info about the person who made this post
	 * @return void
	 */
	public function postCreated(array &$msgOptions, array &$topicOptions, array &$posterOptions)
	{
		return true;
		//	!! SphinxQL Does support updating the search index from its QL interface.
		//	!! Sphinx for SMF does not support this at this time. Code is provided
		//	!! here as examples/testing purposes.

		global $smcFunc, $modSettings;

		// Create an instance of the sphinx client.
		$mySphinx = $this->dbfunc_connect();

		// Figure out our weights.
		$weight_factors = array(
			'age',
			'length',
			'first_message',
			'sticky',
		);
		$weight = array();
		$weight_total = 0;
		foreach ($weight_factors as $weight_factor)
		{
			$weight[$weight_factor] = empty($modSettings['search_weight_' . $weight_factor]) ? 0 : (int) $modSettings['search_weight_' . $weight_factor];
			$weight_total += $weight[$weight_factor];
		}
		if ($weight_total === 0)
		{
			$weight = array(
				'age' => 25,
				'length' => 25,
				'first_message' => 25,
				'sticky' => 25,
			);
			$weight_total = 100;
		}

		// The data was inserted at this point, lets get some data as the passed variables don't contain all we need.
		$request = $smcFunc['db_query']('', '
			SELECT 
				m.id_msg, m.id_topic, m.id_board, IF(m.id_member = 0, 4294967295, m.id_member) AS id_member, m.poster_time, m.body, m.subject,
				t.num_replies + 1 AS num_replies,
				CEILING(1000000 * (
					IF(m.id_msg < 0.7 * s.value, 0, (m.id_msg - 0.7 * s.value) / (0.3 * s.value)) * {int:weight_age} +
					IF(t.num_replies < 200, t.num_replies / 200, 1) * {int:weight_length} +
					IF(m.id_msg = t.id_first_msg, 1, 0) * {int:weight_first_msg} +
					IF(t.is_sticky = 0, 0, 1) * {int:weight_sticky}
				) / {int:weight_total) AS relevance
			FROM {db_prefix}messages AS m
				INNER JOIN {db_prefix}topics AS t ON (t.id_topic = m.id_topic)
			WHERE m.id_msg = {int:newMessage}',
			array(
				'newMessage' => $msgOptions['id'],
				'weight_age' => $weight['age'],
				'weight_length' => $weight['length'],
				'weight_first_msg' => $weight['first_message'],
				'weight_sticky' => $weight['sticky'],
				'weight_total' => $weight_total,
			)
		);
		$tempMessage = $smcFunc['db_fetch_assoc']($request);
		$smcFunc['db_free_result']($request);

		$insertValues = array(
			'id_msg' => $tempMessage['id_msg'],
			'id_topic' => $tempMessage['id_topic'],
			'id_board' => $tempMessage['id_board'],
			'id_member' => $tempMessage['id_member'],
			'poster_time' => $tempMessage['poster_time'],
			'body' => '"' . $tempMessage['body'] . '"',
			'subject' => '"' . $tempMessage['subject'] . '"',
			'num_replies' => $tempMessage['num_replies'],
			'relevance' => $tempMessage['relevance'],
		);

		// The insert query, use replace to make sure we don't get duplicates.
		$query = '
			REPLACE INTO smf_index (' . implode(', ', array_keys($insertValues)) . ')
			VALUES (' . implode(', ', array_values($insertValues)) . ')';

		// Execute the search query.
		$request = $this->dbfunc_query($query, $mySphinx);

		// Can a connection to the daemon be made?
		if ($request === false)
		{
			// Just log the error.
			if ($this->dbfunc_error($mySphinx))
				log_error($this->dbfunc_error($mySphinx));

			// Silently bail out, We can let the reindex cron take care of fixing this.
			return true;
		}

		return true;
	}

	/**
	 * Callback when a post is modified
	 * @see modifyPost()
	 *
	 * @access public
	 * @param array $msgOptions An array of post data
	 * @param array $topicOptions An array of topic data
	 * @param array $posterOptions An array of info about the person who made this post
	 * @return void
	 */
	public function postModified(array &$msgOptions, array &$topicOptions, array &$posterOptions)
	{
		return true;
		//	!! SphinxQL Does support updating the search index from its QL interface.
		//	!! Sphinx for SMF does not support this at this time. Code is provided
		//	!! here as examples/testing purposes.

		// Just call the postCreated as it does a replace.
		$this->postCreated($msgOptions, $topicOptions, $posterOptions);
	}

	/**
	 * Callback when a post is removed, not recycled.
	 *
	 * @access public
	 * @param int $id_msg The ID of the post that was removed
	 * @return void
	 */
	public function postRemoved($id_msg)
	{
		return true;
		//	!! SphinxQL Does support updating the search index from its QL interface.
		//	!! Sphinx for SMF does not support this at this time. Code is provided
		//	!! here as examples/testing purposes.

		global $smcFunc, $modSettings;

		// Create an instance of the sphinx client.
		$mySphinx = $this->dbfunc_connect();

		// SMF only calls this search API when we delete, not recycle. So this will always be a remove.
		$query = '
			DELETE FROM smf_index
			WHERE id_msg = ' . $id_msg;

		// Execute the search query.
		$request = $this->dbfunc_query($query, $mySphinx);

		// Can a connection to the daemon be made?
		if ($request === false)
		{
			// Just log the error.
			if ($this->dbfunc_error($mySphinx))
				log_error($this->dbfunc_error($mySphinx));

			// Silently bail out, We can let the reindex cron take care of fixing this.
			return true;
		}

		return true;
	}

	/**
	 * Callback when a topic is removed
	 *
	 * @access public
	 * @param array $topics The ID(s) of the removed topic(s)
	 * @return void
	 */
	public function topicsRemoved(array $topics)
	{
		return true;
		//	!! SphinxQL Does support updating the search index from its QL interface.
		//	!! Sphinx for SMF does not support this at this time. Code is provided
		//	!! here as examples/testing purposes.

		global $smcFunc, $modSettings;

		// Create an instance of the sphinx client.
		$mySphinx = $this->dbfunc_connect();

		// Execute the search query.
		$request = $this->dbfunc_query($query, $mySphinx);

		// Can a connection to the daemon be made?
		if ($request === false)
		{
			// Just log the error.
			if ($this->dbfunc_error($mySphinx))
				log_error($this->dbfunc_error($mySphinx));

			// Silently bail out, We can let the reindex cron take care of fixing this.
			return true;
		}

		return true;
	}

	/**
	 * Callback when a topic is moved
	 *
	 * @access public
	 * @param array $topics The ID(s) of the moved topic(s)
	 * @param int $board_to The board that the topics were moved to
	 * @return void
	 */
	public function topicsMoved(array $topics, $board_to)
	{
		return ture;
		//	!! SphinxQL Does support updating the search index from its QL interface.
		//	!! Sphinx for SMF does not support this at this time. Code is provided
		//	!! here as examples/testing purposes.

		global $smcFunc, $modSettings;

		// Create an instance of the sphinx client.
		$mySphinx = $this->dbfunc_connect();

		// SMF only calls this search API when we delete, not recycle. So this will always be a remove.
		$query = '
			UPDATE smf_index
			SET id_board = ' . $board_to . '
			WHERE id_topic IN (' . implode(', ', $topics) . ')';

		// Execute the search query.
		$request = $this->dbfunc_query($query, $mySphinx);

		// Can a connection to the daemon be made?
		if ($request === false)
		{
			// Just log the error.
			if ($this->dbfunc_error($mySphinx))
				log_error($this->dbfunc_error($mySphinx));

			// Silently bail out, We can let the reindex cron take care of fixing this.
			return true;
		}

		return true;
	}
}