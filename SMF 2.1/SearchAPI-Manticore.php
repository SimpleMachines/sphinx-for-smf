<?php

/**
 * Simple Machines Forum (SMF)
 *
 * @package SMF
 * @author Simple Machines https://www.simplemachines.org
 * @copyright 2023 Simple Machines and individual contributors
 * @license https://www.simplemachines.org/about/smf/license.php BSD
 *
 * The Following fixes a bug in SMF to show this in the settings section.
 * SearchAPI-Manticore.php
 * @version 2.1.3
 */

if (!defined('SMF'))
	die('Hacking attempt...');

/**
 * Our Search API class
 * @package SearchAPI
 */
class manticore_search extends search_api
{
	/**
	 * @var string The last version of SMF that this was tested on. Helps protect against API changes.
	 */
	public $version_compatible = 'SMF 2.1.99';

	/**
	 * @var string The minimum SMF version that this will work with
	 */
	public $min_smf_version = 'SMF 2.1.0';

	/**
	 * @var bool Whether or not it's supported
	 */
	public $is_supported = true;

	/**
	 * @var array What databases support the manticore index
	 */
	protected $supported_databases = array('mysql', 'mysqli');

	/**
	 * Gets things started, figures out if this is supported and setups mysqli if needed.
	 *
	 * @access public
	 */
	public function __construct()
	{
		global $db_type, $txt, $modSettings;

		loadLanguage('Admin-Manticore');

		// Is this database supported?
		if (!in_array($db_type, $this->supported_databases))
		{
			$this->is_supported = false;
			return;
		}
	}

	/**
	 * Check whether the search can be performed by this API.
	 *
	 * @access public
	 * @param string $methodName The method we would like to use.
	 * @param mixed $query_params The query parameters used for advanced or more defined support checking.
	 * @return bool true or false whether this is supported.
	 */
	public function supportsMethod($methodName, $query_params = null)
	{
		switch ($methodName)
		{
			case 'searchSort':
			case 'prepareIndexes':
			case 'indexedWordQuery':
			case 'searchQuery':
			case 'isValid':
				return true;
			break;

			// We don't support these yet.
			case 'topicsMoved':
			case 'topicsRemoved':
			case 'postRemoved':
			case 'postModified':
			case 'postCreated':
				return false;
			break;

			default:
				// All other methods, too bad dunno you.
				return false;
			return false;
		}
	}

	public function isValid()
	{
		return true;
	}

	/**
	 * The Admin Search Settings calls this in order to define extra API settings.
	 *
	 * @access public
	 * @param array $config_vars All the configuration variables, we have to append or merge these.
	 */
	public static function searchSettings(&$config_vars)
	{
		global $txt, $scripturl, $context, $settings, $sc, $modSettings;

		loadLanguage('Admin-Manticore');

		if (isset($_GET['generateConfig']))
			generateManticoreConfig();

		$local_config_vars = array(
			array('title', 'manticore_server_config_tittle'),
			'</strong><small><em>' . $txt['manticore_server_config_note'] . '</em></small><strong>',
			array('text', 'manticore_index_name', 65, 'default_value' => 'smf', 'subtext' => $txt['manticore_index_name_subtext']),
			array('text', 'manticore_data_path', 65, 'default_value' => '/var/lib/manticore/data', 'subtext' => $txt['manticore_data_path_subtext']),
			array('text', 'manticore_log_path', 65, 'default_value' => '/var/log/manticore', 'subtext' => $txt['manticore_log_path_subtext']),
			array('text', 'manticore_conf_path', 65, 'default_value' => '/etc/manticoresearch', 'subtext' => $txt['manticore_conf_path_subtext']),
			array('text', 'manticore_pid_path', 65, 'default_value' => '/var/run/manticore', 'subtext' => $txt['manticore_pid_path_subtext']),
			array('text', 'manticore_bin_path', 65, 'default_value' => '/usr/bin', 'subtext' => $txt['manticore_bin_path_subtext']),
			array('text', 'manticore_stopword_path', 65, 'default_value' => '', 'subtext' => $txt['manticore_stopword_path_subtext']),
			array('int', 'manticore_indexer_mem', 6, 'default_value' => '32', 'subtext' => $txt['manticore_indexer_mem_subtext'], 'postinput' => $txt['manticore_indexer_mem_postinput']),
			array('int', 'manticore_indexer_mem', 6, 'default_value' => '32', 'subtext' => $txt['manticore_indexer_mem_subtext'], 'postinput' => $txt['manticore_indexer_mem_postinput']),

			// SMF Configuration Settings.
			array('title', 'manticore_smf_manticore_tittle'),
			array('text', 'manticore_searchd_server', 32, 'default_value' => 'localhost', 'subtext' => $txt['manticore_searchd_server_subtext']),
			array('check', 'manticore_searchd_bind', 0, 'subtext' => $txt['manticore_searchd_bind_subtext']),
			array('int', 'manticore_searchd_port', 6, 'default_value' => '9306', 'subtext' => $txt['manticore_searchd_port_subtext']),
			array('int', 'manticore_version', 6, 'default_value' => '3.0', 'subtext' => $txt['manticore_version_subtext']),
			array('int', 'manticore_max_results', 6, 'default_value' => '1000', 'subtext' => $txt['manticore_max_results_subtext']),

			// Just a hints section.
			array('title', 'manticore_config_hints_title'),
			array('callback', 'SMFAction_Manticore_Hints'),
		);

		// Merge them in.
		$config_vars = array_merge($config_vars, $local_config_vars);

		$context['post_url'] = $scripturl . '?action=admin;area=modsettings;save;sa=manticore';
		$context['settings_title'] = $txt['manticore_server_config_tittle'];
		$context['manticore_version'] = self::manticoreversion();

		// Try to fall back.
		if (empty($context['manticore_version']) && !empty($context['manticore_version']))
			$context['manticore_version'] = $modSettings['manticore_version'];
		else if (!empty($context['manticore_version']) && empty($context['manticore_version']))
			$modSettings['manticore_version'] = $context['manticore_version'];
		else
			$context['sphinx_version'] = '4.2.0';

		// Saving?
		if (isset($_GET['save']))
		{
			// Make sure this exists, but just push it with the other changes.
			if (!isset($modSettings['manticore_indexed_msg_until']))
				$config_vars[] = array('int', 'manticore_indexed_msg_until', 'default_value' => 1);

			// We still need a port.
			if (empty($_POST['manticore_searchd_port']))
				$_POST['manticore_searchd_port'] = 9306;
		}

		// This hacks in some defaults that are needed to generate a proper configuration file.
		foreach ($config_vars as $id => $cv)
			if (is_array($cv) && isset($cv[1], $cv['default_value']) && !isset($modSettings[$cv[1]]))
				$config_vars[$id]['value'] = $cv['default_value'];
	}

	/**
	 * Callback function for usort used to sort the fulltext results.
	 * the order of sorting is: large words, small words, large words that
	 * are excluded from the search, small words that are excluded.
	 *
	 * @access public
	 * @param string $a Word A
	 * @param string $b Word B
	 * @return int An integer indicating how the words should be sorted
	 */
	public function searchSort($a, $b)
	{
		global $modSettings, $excludedWords;

		$x = strlen($a) - (in_array($a, $excludedWords) ? 1000 : 0);
		$y = strlen($b) - (in_array($b, $excludedWords) ? 1000 : 0);

		return $x < $y ? 1 : ($x > $y ? -1 : 0);
	}

	/**
	 * Callback while preparing indexes for searching
	 *
	 * @access public
	 * @param string $word A word to index
	 * @param array $wordsSearch Search words
	 * @param array $wordsExclude Words to exclude
	 * @param bool $isExcluded Whether the specfied word should be excluded
	 */
	public function prepareIndexes($word, array &$wordsSearch, array &$wordsExclude, $isExcluded)
	{
		global $modSettings;

		$subwords = text2words($word, null, false);

		$fulltextWord = count($subwords) === 1 ? $word : '"' . $word . '"';
		$wordsSearch['indexed_words'][] = $fulltextWord;
		if ($isExcluded)
			$wordsExclude[] = $fulltextWord;
	}

	/**
	 * Callback for actually performing the search query
	 *
	 * @access public
	 * @param array $query_params An array of parameters for the query
	 * @param array $searchWords The words that were searched for
	 * @param array $excludedIndexWords Indexed words that should be excluded
	 * @param array $participants - Only used if we have enabled participation.
	 * @param array $searchArray - Builds $context['key_words'] used for highlighting
	 * @return mixed
		- Both $participants and $searchArray are updated by reference
		- $context['topics'] is populated with a id_msg => array(
						'id' => id_topic
						'relevance' => round(relevance / 10000, 1) . '%',
						'num_matches' => A topic is specififed (ie, searching one topic only) ? $num_rows : 0,
						'matches' => array(),
					),			
	 */
	public function searchQuery(array $query_params, array $searchWords, array $excludedIndexWords, array &$participants, array &$searchArray)
	{
		global $user_info, $context, $modSettings;

		// Only request the results if they haven't been cached yet.
		if (($cached_results = cache_get_data('Xsearch_results_' . md5($user_info['query_see_board'] . '_' . $context['params']))) === null)
		{
			// Create an instance of the manticore client.
			$myManticore = $this->dbfunc_connect();

			// Make sure we have a max results.
			if (!isset($modSettings['manticore_max_results']))
				$modSettings['manticore_max_results'] = '1000';

			// Compile different options for our query
			$query = 'SELECT * FROM ' . self::indexName() . '_index';

			// Construct the (binary mode) query.
			$where_match = $this->_constructQuery($query_params['search']);
			// Nothing to search, return zero results
			if (trim($where_match) == '')
				return 0;

			if ($query_params['subject_only'])
				$where_match = '@subject ' . $where_match;

			$query .= ' WHERE MATCH(\'' . $where_match . '\')';

			// Set the limits based on the search parameters.
			$extra_where = array();
			if (!empty($query_params['min_msg_id']) || !empty($query_params['max_msg_id']))
				$extra_where[] = 'id >= ' . $query_params['min_msg_id'] . ' AND id <=' . (empty($query_params['max_msg_id']) ? (int) $modSettings['maxMsgID'] : $query_params['max_msg_id']);
			if (!empty($query_params['topic']))
				$extra_where[] = 'id_topic = ' . (int) $query_params['topic'];
			if (!empty($query_params['brd']) && is_array($query_params['brd']))
				$extra_where[] = 'id_board IN (' . implode(',', $query_params['brd']) . ')';
			if (!empty($query_params['memberlist']) && is_array($query_params['memberlist']))
				$extra_where[] = 'id_member IN (' . implode(',', $query_params['memberlist']) . ')';

			if (!empty($extra_where) && is_array($extra_where))
				$query .= ' AND ' . implode(' AND ', $extra_where);

			// Put together a sort string; besides the main column sort (relevance, id_topic, or num_replies), add secondary sorting based on relevance value (if not the main sort method) and age
			$manticore_sort = ($query_params['sort'] === 'id_msg' ? 'id_topic' : $query_params['sort']) . ' ' . strtoupper($query_params['sort_dir']) . ($query_params['sort'] === 'relevance' ? '' : ', relevance desc') . ', poster_time DESC';
			// Grouping by topic id makes it return only one result per topic, so don't set that for in-topic searches
			if (empty($query_params['topic']))
				$query .= ' GROUP BY id_topic WITHIN GROUP ORDER BY ' . $manticore_sort;
			$query .= ' ORDER BY ' . $manticore_sort;

			$query .= ' LIMIT 0,' . (int) $modSettings['manticore_max_results'];

			// Any limitations we need to add?
			if (!empty($modSettings['manticore_max_results']) && (int) $modSettings['manticore_max_results'] > 0)
				$query .= ' OPTION max_matches=' . (int) $modSettings['manticore_max_results'];

			// Execute the search query.
			$request = $this->dbfunc_query($query, $myManticore);

			// Can a connection to the daemon be made?
			if ($request === false)
			{
				// Just log the error.
				if ($this->dbfunc_error($myManticore))
					log_error($this->dbfunc_error($myManticore));
				fatal_lang_error('error_no_search_daemon');
			}

			// Get the relevant information from the search results.
			$cached_results = array(
				'matches' => array(),
			);
			$num_rows = $this->dbfunc_num_rows($request);
			if ($num_rows != 0)
				while($match = $this->dbfunc_fetch_assoc($request))
					$cached_results['matches'][$match['id']] = array(
						'id' => $match['id_topic'],
						'relevance' => round($match['relevance'] / 10000, 1) . '%',
						'num_matches' => empty($query_params['topic']) ? $num_rows : 0,
						'matches' => array(),
					);
			$this->dbfunc_free_result($request);
			$this->dbfunc_close($myManticore);

			$cached_results['total'] = count($cached_results['matches']);

			// Store the search results in the cache.
			cache_put_data('search_results_' . md5($user_info['query_see_board'] . '_' . $context['params']), $cached_results, 600);
		}

		$participants = array();
		foreach (array_slice(array_keys($cached_results['matches']), (int) $_REQUEST['start'], $modSettings['search_results_per_page']) as $msgID)
		{
			$context['topics'][$msgID] = $cached_results['matches'][$msgID];
			$participants[$cached_results['matches'][$msgID]['id']] = false;
		}

		// Sentences need to be broken up in words for proper highlighting.
		$searchArray = array();
		foreach ($searchWords as $orIndex => $words)
			$searchArray = array_merge($searchArray, $searchWords[$orIndex]['subject_words']);

		// Work around SMF bug causing multiple pages to not work right.
		if (!isset($_SESSION['search_cache']['num_results']))
			$_SESSION['search_cache'] = [
				'num_results' => $cached_results['total']
			];

		return $cached_results['total'];
	}

	/**
	 * Constructs a binary mode query to pass back to manticore
	 *
	 * @param string $string The user entered query to construct with
	 * @return string A binary mode query
	 */
	private function _constructQuery($string)
	{
		$keywords = array('include' => array(), 'exclude' => array());

		// Split our search string and return an empty string if no matches
		if (!preg_match_all('~ (-?)("[^"]+"|[^" ]+)~', ' ' . $string , $tokens, PREG_SET_ORDER))
			return '';

		// First we split our string into included and excluded words and phrases
		$or_part = FALSE;
		foreach ($tokens as $token)
		{
			// Strip the quotes off of a phrase
			if ($token[2][0] == '"')
			{
				$token[2] = substr($token[2], 1, -1);
				$phrase = TRUE;
			}
			else
				$phrase = FALSE;

			// Prepare this token
			$cleanWords = $this->_cleanString($token[2]);

			// Explode the cleanWords again incase the cleaning put more spaces into it
			$addWords = $phrase ? array('"' . $cleanWords . '"') : preg_split('~ ~u', $cleanWords, -1, PREG_SPLIT_NO_EMPTY);

			if ($token[1] == '-')
				$keywords['exclude'] = array_merge($keywords['exclude'], $addWords);

			// OR'd keywords (we only do this if we have something to OR with)
			elseif (($token[2] == 'OR' || $token[2] == '|') && count($keywords['include']))
			{
				$last = array_pop($keywords['include']);
				if (!is_array($last))
					$last = array($last);
				$keywords['include'][] = $last;
				$or_part = TRUE;
				continue;
			}

			// AND is implied in a Manticore Search
			elseif ($token[2] == 'AND' || $token[2] == '&')
				continue;

			// If this part of the query ended up being blank, skip it
			elseif (trim($cleanWords) == '')
				continue;

			// Must be something they want to search for!
			else
			{
				// If this was part of an OR branch, add it to the proper section
				if ($or_part)
					$keywords['include'][count($keywords['include']) - 1] = array_merge($keywords['include'][count($keywords['include']) - 1], $addWords);
				else
					$keywords['include'] = array_merge($keywords['include'], $addWords);
			}

			// Start fresh on this...
			$or_part = FALSE;
		}

		// Let's make sure they're not canceling each other out
		if (!count(array_diff($keywords['include'], $keywords['exclude'])))
			return '';

		// Now we compile our arrays into a valid search string
		$query_parts = array();
		foreach ($keywords['include'] as $keyword)
			$query_parts[] = is_array($keyword) ? '(' . implode(' | ', $keyword) . ')' : $keyword;

		foreach ($keywords['exclude'] as $keyword)
			$query_parts[] = '-' . $keyword;

		return implode(' ', $query_parts);
	}

	/**
	 * Cleans a string of everything but alphanumeric characters
	 *
	 * @param string $string A string to clean
	 * @return string A cleaned up string
	 */
	private function _cleanString($string)
	{
		global $smcFunc;

		// Decode the entities first
		$string = html_entity_decode($string, ENT_QUOTES, 'UTF-8');

		// Lowercase string
		$string = $smcFunc['strtolower']($string);

		// Fix numbers so they search easier (phone numbers, SSN, dates, etc)
		$string = preg_replace('~([[:digit:]]+)\pP+(?=[[:digit:]])~u', '', $string);

		// Last but not least, strip everything out that's not alphanumeric or a underscore.
		$string = preg_replace('~[^\pL\pN_]+~u', ' ', $string);

		return $string;
	}

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
		//	!! Manticore Does support updating the search index from its Plain Text index.
		//	!! Manticore for SMF does not support this at this time. Code is provided
		//	!! here as examples/testing purposes.

		global $smcFunc, $modSettings;

		// Create an instance of the manticore client.
		$myManticore = $this->dbfunc_connect();

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
			REPLACE INTO ' . self::indexName() . '_index (' . implode(', ', array_keys($insertValues)) . ')
			VALUES (' . implode(', ', array_values($insertValues)) . ')';

		// Execute the search query.
		$request = $this->dbfunc_query($query, $myManticore);

		// Can a connection to the daemon be made?
		if ($request === false)
		{
			// Just log the error.
			if ($this->dbfunc_error($myManticore))
				log_error($this->dbfunc_error($myManticore));

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
		//	!! Manticore Does support updating the search index from its Plain Text index.
		//	!! Manticore for SMF does not support this at this time. Code is provided
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
		//	!! Manticore Does support updating the search index from its Plain Text index.
		//	!! Manticore for SMF does not support this at this time. Code is provided
		//	!! here as examples/testing purposes.

		global $smcFunc, $modSettings;

		// Create an instance of the manticore client.
		$myManticore = $this->dbfunc_connect();

		// SMF only calls this search API when we delete, not recycle. So this will always be a remove.
		$query = '
			DELETE FROM ' . self::indexName() . '_index
			WHERE id_msg = ' . $id_msg;

		// Execute the search query.
		$request = $this->dbfunc_query($query, $myManticore);

		// Can a connection to the daemon be made?
		if ($request === false)
		{
			// Just log the error.
			if ($this->dbfunc_error($myManticore))
				log_error($this->dbfunc_error($myManticore));

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
		//	!! Manticore Does support updating the search index from its Plain Text index.
		//	!! Manticore for SMF does not support this at this time. Code is provided
		//	!! here as examples/testing purposes.

		global $smcFunc, $modSettings;

		// Create an instance of the manticore client.
		$myManticore = $this->dbfunc_connect();

		// SMF only calls this search API when we delete, not recycle. So this will always be a remove.
		$query = '
			DELETE FROM ' . self::indexName() . '_index
			WHERE id_topic IN (' . implode(', ', $topics) . ')';

		// Execute the search query.
		$request = $this->dbfunc_query($query, $myManticore);

		// Can a connection to the daemon be made?
		if ($request === false)
		{
			// Just log the error.
			if ($this->dbfunc_error($myManticore))
				log_error($this->dbfunc_error($myManticore));

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
		return true;
		//	!! Manticore Does support updating the search index from its Plain Text index.
		//	!! Manticore for SMF does not support this at this time. Code is provided
		//	!! here as examples/testing purposes.

		global $smcFunc, $modSettings;

		// Create an instance of the manticore client.
		$myManticore = $this->dbfunc_connect();

		// SMF only calls this search API when we delete, not recycle. So this will always be a remove.
		$query = '
			UPDATE ' . self::indexName() . '_index
			SET id_board = ' . $board_to . '
			WHERE id_topic IN (' . implode(', ', $topics) . ')';

		// Execute the search query.
		$request = $this->dbfunc_query($query, $myManticore);

		// Can a connection to the daemon be made?
		if ($request === false)
		{
			// Just log the error.
			if ($this->dbfunc_error($myManticore))
				log_error($this->dbfunc_error($myManticore));

			// Silently bail out, We can let the reindex cron take care of fixing this.
			return true;
		}

		return true;
	}

	/**
	 * Manticore Database Support API: connect
	 *
	 * @access private
	 * @param string $host The manticore search address, this will default to $modSettings['manticore_searchd_server'].
	 * @param string $port The port Manticore runs on, this will default to $modSettings['manticore_searchd_port'].
	 * @return resource
	 */
	private function dbfunc_connect(string $host = '', string $port = '')
	{
		global $modSettings, $txt;

		// Fill out our host and port if needed.
		if (empty($host))
			$host = $modSettings['manticore_searchd_server'] == 'localhost' ? '127.0.0.1' : $modSettings['manticore_searchd_server'];
		if (empty($port))
			$port = empty($modSettings['manticore_searchd_port']) ? 9306 : (int) $modSettings['manticore_searchd_port'];

		$myManticore = @mysqli_connect($host, '', '', '', $port);

		// Mysqli is never a resource, but an object.
		if (!is_object($myManticore) || $myManticore->connect_errno > 0)
		{
			loadLanguage('Errors');
			fatal_error($txt['error_no_search_daemon']);
		}			

		return $myManticore;
	}
	/**
	 * Manticore Database Support API: query
	 *
	 * @access private
	 * @param string $query The query to run.
	 * @param resource $myManticore A Manticore connection resource.
	 * @return resource
	 */
	private function dbfunc_query(string $query, $myManticore)
	{
		return mysqli_query($myManticore, $query);
	}

	/**
	 * Manticore Database Support API: num_rows
	 *
	 * @access private
	 * @param resource $myManticore A Manticore request resource.
	 * @return int|string
	 */
	private function dbfunc_num_rows($myManticore)
	{
		return mysqli_num_rows($myManticore);
	}

	/**
	 * Manticore Database Support API: fetch_assoc
	 *
	 * @access private
	 * @param resource $myManticore A Manticore request resource.
	 * @return array
	 */
	private function dbfunc_fetch_assoc($myManticore)
	{
		return mysqli_fetch_assoc($myManticore);
	}

	/**
	 * Manticore Database Support API: free_result
	 *
	 * @access private
	 * @param resource $myManticore A Manticore request resource.
	 * @return void
	 */
	private function dbfunc_free_result($myManticore)
	{
		return mysqli_free_result($myManticore);
	}

	/**
	 * Manticore Database Support API: free_result
	 *
	 * @access private
	 * @param resource $myManticore A Manticore connection resource.
	 * @return bool
	 */
	private function dbfunc_close($myManticore)
	{
		return mysqli_close($myManticore);
	}

	/**
	 * Manticore Database Support API: error
	 *
	 * @access private
	 * @param resource $myManticore A Manticore connection resource.
	 * @return string
	 */
	private function dbfunc_error($myManticore)
	{
		return mysqli_error($myManticore);
	}

	/**
	 * Manticore Version
	 *
	 * @access private
	 * @return decimal The Major + minor version of Manticore.
	 */
	private static function manticoreversion()
	{
		global $modSettings;

		if (empty($modSettings['manticore_bin_path']))
			$modSettings['manticore_bin_path'] = '/usr/bin';

		// Try to safely check for the indexer file, but do this in a way we can catch the error so PHP doesn't output it.
		try {
			set_error_handler(static function ($severity, $message, $file, $line) {
				throw new \ErrorException($message, 0, $severity, $file, $line);
			});

			if (!file_exists(realpath($modSettings['sphinx_bin_path'] . '/indexer')))
				return;
		} catch (\Throwable $e) {
			return;
		} finally {
			restore_error_handler();
		}

		$binary = realpath($modSettings['manticore_bin_path'] . '/indexer');

		$raw_version = shell_exec($binary . ' -v');

		if (empty($raw_version))
			return;

		preg_match('~Manticore (\d+)\.(\d+)~i', $raw_version, $m);		

		// No version?
		if (empty($m) || empty($m[1]) || empty($m[2]))
			return;

		return $m[1] . '.' . $m[2];
	}

	/**
	 * Index name
	 *
	 * @access private
	 * @return string The name of the idnex.
	 */
	private static function indexName()
	{
		global $modSettings;
		return !empty($modSettings['manticore_index_name']) ? $modSettings['manticore_index_name'] : 'smf';
	}
}

/**
 * Callback to a template from our admin search settings page.
 * This is used to generate hints and links to generate the Manticore
 * configuration file.
 *
 * @access public
 */
function template_callback_SMFAction_Manticore_Hints()
{
	global $db_type, $scripturl, $txt, $modSettings;

	if (!isset($modSettings['manticore_data_path'], $modSettings['manticore_log_path']))
	{
		echo '
				<dt></dt>
				<dd>', $txt['manticore_config_hints_save'], '</dd>';

		return;
	}

	// Ensure these exist.
	$index_name = !empty($modSettings['sphinx_index_name']) ? $modSettings['sphinx_index_name'] : 'smf';
	if (empty($modSettings['manticore_conf_path']))
		$modSettings['manticore_conf_path'] = '/etc/manticoresearch';
	if (empty($modSettings['manticore_bin_path']))
		$modSettings['manticore_bin_path'] = '/usr/bin';

	echo '
				<dt></dt>
				<dd><a href="', $scripturl, '?action=admin;area=managesearch;sa=weights">', $txt['search_weights'], '</a></dd>
				<dd>[<a href="', $scripturl, '?action=admin;area=managesearch;sa=settings;generateConfig;view" target="_blank">', $txt['manticore_view_config'], '</a> | <a href="', $scripturl, '?action=admin;area=managesearch;sa=settings;generateConfig">', $txt['manticore_download_config'], '</a>] (', $txt['manticore_config_hints_save'], ')</dd>
			</dl>';

	$message = '
		' . sprintf($txt['manticore_config_hints_desc'], $modSettings['manticore_data_path']) . '[pre]mkdir -p ' . $modSettings['manticore_data_path'] . '
mkdir -p ' . $modSettings['manticore_log_path'] . '
chmod a+w ' . $modSettings['manticore_data_path'] . '
chmod a+w ' . $modSettings['manticore_log_path'] . '[/pre]';

	// Add a extra step for postgresql.
	if ($db_type == 'postgresql')
		$message .= '
		[hr]
		' . $txt['manticore_config_hints_pgsql_func'] . '
		[code]
CREATE FUNCTION update_settings(var TEXT, val INT) RETURNS VOID AS $$
	BEGIN
		LOOP
			-- first try to update the key
			UPDATE PREFIX_settings SET value = val WHERE variable = var;
			IF found THEN
				RETURN;
			END IF;
			-- not there so try to insert the key
			BEGIN
				INSERT INTO PREFIX_settings(variable,value) VALUES (var,val);
			RETURN;
			EXCEPTION WHEN unique_violation THEN
				-- do nothing, loop again to try the UPDATE
			END;
		END LOOP;
	END;
$$
LANGUAGE plpgsql;[/code]';

	$message .= '
		[hr]
		' . $txt['manticore_config_hints_index_start'] . '[pre]sudo -u manticore ' . $modSettings['manticore_bin_path'] . '/indexer --config ' . $modSettings['manticore_conf_path'] . '/manticore.conf --all
sudo -u manticore ' . $modSettings['manticore_bin_path'] . '/searchd --config ' . $modSettings['manticore_conf_path'] . '/manticore.conf[/pre]
		' . $txt['manticore_config_hints_index_finish'] . '
		[hr]
		' . $txt['manticore_config_hints_cron_start'] . '[pre]# search indexer
10 3 * * * ' . $modSettings['manticore_bin_path'] . '/indexer --config ' . $modSettings['manticore_conf_path'] . '/manticore.conf --rotate ' . $index_name . '_base_index
0 * * * * ' . $modSettings['manticore_bin_path'] . '/indexer --config ' . $modSettings['manticore_conf_path'] . '/manticore.conf --rotate ' . $index_name . '_delta_index[/pre]';

	// Print out our message.
	echo parse_bbc($message);

	echo '
					<dl class="settings">';
}

// This is the manticore configuration file.
/**
 * The Manticore generated configuration file.  We perform some checks and
 * calculation and then issue a download with the appropriate setup.
 *
 * @access public
 */
function generateManticoreConfig()
{
	global $context, $db_server, $db_name, $db_user, $db_passwd, $db_prefix;
	global $db_type, $db_character_set, $modSettings;

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

	if ($db_type == 'postgresq')
		$supported_db_type = 'pgsql';
	else
		$supported_db_type = 'mysql';

	$host = $modSettings['manticore_searchd_server'] == 'localhost' ? '127.0.0.1' : $modSettings['manticore_searchd_server'];
	$index_name = !empty($modSettings['manticore_index_name']) ? $modSettings['manticore_index_name'] : 'smf';

	// Lets fall out of SMF templating and start the headers to serve a file.
	ob_end_clean();
	ob_start();

	// Send the attachment headers.
	header('Pragma: ');
	if (!$context['browser']['is_gecko'])
		header('Content-Transfer-Encoding: binary');
	header('Expires: ' . gmdate('D, d M Y H:i:s', time() + 525600 * 60) . ' GMT');
	header('Last-Modified: ' . gmdate('D, d M Y H:i:s', time()) . ' GMT');
	header('Accept-Ranges: bytes');
	header('Connection: close');
	header('ETag: ' . sha1('manticore.conf' . time()));

	if (isset($_GET['view']))
		header('Content-Type: text/plain');
	else
	{
		header('Content-Type: ' . ($context['browser']['is_ie'] || $context['browser']['is_opera'] ? 'application/octetstream' : 'application/octet-stream'));
		header('Content-Disposition: attachment; filename="manticore.conf"');
	}

	header('Cache-Control: max-age=' . (525600 * 60) . ', private');

	// At this point, we are generating the configuration file.
	echo '#
# Manticore configuration file (manticore.conf), configured for SMF 2.1
#
# By default the location of this file would probably be:
# ' . (empty($modSettings['manticore_conf_path']) ? '/etc/manticoresearch' : $modSettings['manticore_conf_path'])  . '/manticore.conf

source ' . $index_name . '_source
{
	type				= ', $supported_db_type, '
	sql_host			= ', $db_server, '
	sql_user			= ', $db_user, '
	sql_pass			= ', $db_passwd, '
	sql_db				= ', $db_name, '
	sql_port			= 3306', empty($db_character_set) ? '' : '
	sql_query_pre		= SET NAMES ' . $db_character_set;

	// Thanks to TheStupidOne for pgsql queries.
	if ($db_type == 'pgsql')
		echo '
	sql_query_pre		= \
		SELECT update_settings(\'manticore_indexed_msg_until\', (SELECT MAX(id_msg) FROM PREFIX_messages))';
	else
		echo '
	sql_query_pre		=	\
		REPLACE INTO ', $db_prefix, 'settings (variable, value) \
		SELECT \'manticore_indexed_msg_until\', MAX(id_msg) \
		FROM ', $db_prefix, 'messages';

	echo '
	sql_query_range		= \
		SELECT 1, value \
		FROM ', $db_prefix, 'settings \
		WHERE variable = \'manticore_indexed_msg_until\'
	sql_range_step = 1000';

	// Thanks to TheStupidOne for pgsql queries.
	if ($db_type == 'pgsql')
		echo '
	sql_query			= \
	SELECT \
		m.id_msg, m.id_topic, m.id_board, CASE WHEN m.id_member = 0 THEN 4294967295 ELSE m.id_member END AS id_member, m.poster_time, m.body, m.subject, \
		t.num_replies + 1 AS num_replies, CEILING(1000000 * ( \
			CASE WHEN m.id_msg < 0.7 * cast(s.value as INT) THEN 0 ELSE (m.id_msg - 0.7 * cast(s.value as INT)) / (0.3 * cast(s.value as INT)) END * ' . $weight['age'] . ' + \
			CASE WHEN t.num_replies < 200 THEN t.num_replies / 200 ELSE 1 END * ' . $weight['length'] . ' + \
			CASE WHEN m.id_msg = t.id_first_msg THEN 1 ELSE 0 END * ' . $weight['first_message'] . ' + \
			CASE WHEN t.is_sticky = 0 THEN 0 ELSE 1 END * ' . $weight['sticky'] . ' \
		) / ' . $weight_total . ') AS relevance \
	FROM ', $db_prefix, 'messages AS m, ', $db_prefix, 'topics AS t, ', $db_prefix, 'settings AS s \
	WHERE t.id_topic = m.id_topic \
		AND s.variable = \'maxMsgID\' \
		AND m.id_msg BETWEEN $start AND $end';
	else
		echo '
	sql_query			=	\
		SELECT \
			m.id_msg, m.id_topic, m.id_board, IF(m.id_member = 0, 4294967295, m.id_member) AS id_member, m.poster_time, m.body, m.subject, \
			t.num_replies + 1 AS num_replies, CEILING(1000000 * ( \
				IF(m.id_msg < 0.7 * s.value, 0, (m.id_msg - 0.7 * s.value) / (0.3 * s.value)) * ' . $weight['age'] . ' + \
				IF(t.num_replies < 200, t.num_replies / 200, 1) * ' . $weight['length'] . ' + \
				IF(m.id_msg = t.id_first_msg, 1, 0) * ' . $weight['first_message'] . ' + \
				IF(t.is_sticky = 0, 0, 1) * ' . $weight['sticky'] . ' \
			) / ' . $weight_total . ') AS relevance \
		FROM ', $db_prefix, 'messages AS m, ', $db_prefix, 'topics AS t, ', $db_prefix, 'settings AS s \
		WHERE t.id_topic = m.id_topic \
			AND s.variable = \'maxMsgID\' \
			AND m.id_msg BETWEEN $start AND $end';

	echo '
	sql_attr_uint		= id_topic
	sql_attr_uint		= id_board
	sql_attr_uint		= id_member
	sql_attr_timestamp	= poster_time
	sql_attr_timestamp	= relevance
	sql_attr_timestamp	= num_replies';

	echo '
}

source ' . $index_name . '_delta_source : ' . $index_name . '_source
{
	sql_query_pre		= ', isset($db_character_set) ? 'SET NAMES ' . $db_character_set : '', '
	sql_query_range		= \
		SELECT s1.value, s2.value \
		FROM ', $db_prefix, 'settings AS s1, ', $db_prefix, 'settings AS s2 \
		WHERE s1.variable = \'manticore_indexed_msg_until\' \
			AND s2.variable = \'maxMsgID\'
}

index ' . $index_name . '_base_index
{
	type				= plain
	html_strip			= 1
	source				= ' . $index_name . '_source
	path				= ', $modSettings['manticore_data_path'], '/' . $index_name . '_manticore_base.index', empty($modSettings['manticore_stopword_path']) ? '' : '
	stopwords			= ' . $modSettings['manticore_stopword_path'], '
	min_word_len		= 2
	charset_table		= 0..9, A..Z->a..z, _, a..z
}

index ' . $index_name . '_delta_index : ' . $index_name . '_base_index
{
	type				= plain
	source				= ' . $index_name . '_delta_source
	path				= ', $modSettings['manticore_data_path'], '/' . $index_name . '_manticore_delta.index
}

index ' . $index_name . '_index
{
	type				= distributed
	local				= ' . $index_name . '_base_index
	local				= ' . $index_name . '_delta_index
}

indexer
{
	mem_limit			= ', (int) $modSettings['manticore_indexer_mem'], 'M
}

searchd
{
	listen				= ', !empty($modSettings['manticore_searchd_bind']) ? $host : '0.0.0.0', ':', (empty($modSettings['manticore_searchd_port']) ? 9306 : (int) $modSettings['manticore_searchd_port']), ':mysql41
	log					= ', $modSettings['manticore_log_path'], '/searchd.log
	query_log			= ', $modSettings['manticore_log_path'], '/query.log
	network_timeout		= 5
	pid_file			= ', $modSettings['manticore_data_path'], '/searchd.pid
	binlog_path			= ', $modSettings['manticore_data_path'], '
}';

	die;
}