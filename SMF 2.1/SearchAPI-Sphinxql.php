<?php

/**
 * Simple Machines Forum (SMF)
 *
 * @package SMF
 * @author Simple Machines https://www.simplemachines.org
 * @copyright 2021 Simple Machines and individual contributors
 * @license https://www.simplemachines.org/about/smf/license.php BSD
 *
 * The Following fixes a bug in SMF to show this in the settings section.
 * SearchAPI-Sphinxql.php
 * @version 2.1 RC4
 */

if (!defined('SMF'))
	die('Hacking attempt...');

/**
 * Our Search API class
 * @package SearchAPI
 */
class sphinxql_search extends search_api
{
	/**
	 * @var string The last version of SMF that this was tested on. Helps protect against API changes.
	 */
	public $version_compatible = 'SMF 2.1.99';

	/**
	 * @var string The minimum SMF version that this will work with
	 */
	public $min_smf_version = 'SMF 2.1 RC4';

	/**
	 * @var bool Whether or not it's supported
	 */
	public $is_supported = true;

	/**
	 * @var array What databases support the sphinx index
	 */
	protected $supported_databases = array('mysql', 'mysqli');

	/**
	 * @var string The database type we are using.
	 * We do not follow SMF's database abstraction here as SphinxQL is its own language and while
	 * similar is not 100% directly compatible with MySQL syntax.
	 */
	private $db_type = 'mysql';

	/**
	 * Gets things started, figures out if this is supported and setups mysqli if needed.
	 *
	 * @access public
	 */
	public function __construct()
	{
		global $db_type, $txt, $modSettings;

		loadLanguage('Admin-Sphinx');

		// Is this database supported?
		if (!in_array($db_type, $this->supported_databases))
		{
			$this->is_supported = false;
			return;
		}

		// We sorta support mysqli at this point.
		if ($db_type == 'mysqli' || (function_exists('mysqli_connect') && !function_exists('mysql_connect')))
			$this->db_type = 'mysqli';
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

		loadLanguage('Admin-Sphinx');

		if (isset($_GET['generateConfig']))
			generateSphinxConfig();

		$local_config_vars = array(
			array('title', 'sphinx_server_config_tittle'),
			'</strong><small><em>' . $txt['sphinx_server_config_note'] . '</em></small><strong>',
			array('text', 'sphinx_data_path', 65, 'default_value' => '/var/sphinx/data', 'subtext' => $txt['sphinx_data_path_subtext']),
			array('text', 'sphinx_log_path', 65, 'default_value' => '/var/sphinx/log', 'subtext' => $txt['sphinx_log_path_subtext']),
			array('text', 'sphinx_conf_path', 65, 'default_value' => '/etc/sphinxsearch', 'subtext' => $txt['sphinx_conf_path_subtext']),
			array('text', 'sphinx_bin_path', 65, 'default_value' => '/usr/bin', 'subtext' => $txt['sphinx_bin_path_subtext']),
			array('text', 'sphinx_stopword_path', 65, 'default_value' => '', 'subtext' => $txt['sphinx_stopword_path_subtext']),
			array('int', 'sphinx_indexer_mem', 6, 'default_value' => '32', 'subtext' => $txt['sphinx_indexer_mem_subtext'], 'postinput' => $txt['sphinx_indexer_mem_postinput']),
			array('int', 'sphinx_indexer_mem', 6, 'default_value' => '32', 'subtext' => $txt['sphinx_indexer_mem_subtext'], 'postinput' => $txt['sphinx_indexer_mem_postinput']),

			// SMF Configuration Settings.
			array('title', 'sphinx_smf_sphinx_tittle'),
			array('text', 'sphinx_searchd_server', 32, 'default_value' => 'localhost', 'subtext' => $txt['sphinx_searchd_server_subtext']),
			array('check', 'sphinx_searchd_bind', 0, 'subtext' => $txt['sphinx_searchd_bind_subtext']),
			// This is for the non legacy QL version, which we are not going support at this time.
			//array('int', 'sphinx_searchd_port', 6, 'default_value' => '9312', 'subtext' => $txt['sphinx_searchd_port_subtext']),
			array('int', 'sphinxql_searchd_port', 6, 'default_value' => '9306', 'subtext' => $txt['sphinxql_searchd_port_subtext']),
			array('int', 'sphinx_max_results', 6, 'default_value' => '1000', 'subtext' => $txt['sphinx_max_results_subtext']),

			// Just a hints section.
			array('title', 'sphinx_config_hints_title'),
			array('callback', 'SMFAction_Sphinx_Hints'),
		);

		// Merge them in.
		$config_vars = array_merge($config_vars, $local_config_vars);

		$context['post_url'] = $scripturl . '?action=admin;area=modsettings;save;sa=sphinx';
		$context['settings_title'] = $txt['sphinx_server_config_tittle'];

		// Saving?
		if (isset($_GET['save']))
		{
			// Make sure this exists, but just push it with the other changes.
			if (!isset($modSettings['sphinx_indexed_msg_until']))
				$config_vars[] = array('int', 'sphinx_indexed_msg_until', 'default_value' => 1);

			// We still need a port.
			if (empty($_POST['sphinxql_searchd_port']))
				$_POST['sphinxql_searchd_port'] = 9306;
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
			// Create an instance of the sphinx client.
			$mySphinx = $this->dbfunc_connect();

			// Make sure we have a max results.
			if (!isset($modSettings['sphinx_max_results']))
				$modSettings['sphinx_max_results'] = '1000';

			// Compile different options for our query
			$query = 'SELECT * FROM smf_index';

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
			if (!empty($query_params['brd']))
				$extra_where[] = 'id_board IN (' . implode(',', $query_params['brd']) . ')';
			if (!empty($query_params['memberlist']))
				$extra_where[] = 'id_member IN (' . implode(',', $query_params['memberlist']) . ')';

			if (!empty($extra_where))
				$query .= ' AND ' . implode(' AND ', $extra_where);

			// Put together a sort string; besides the main column sort (relevance, id_topic, or num_replies), add secondary sorting based on relevance value (if not the main sort method) and age
			$sphinx_sort = ($query_params['sort'] === 'id_msg' ? 'id_topic' : $query_params['sort']) . ' ' . strtoupper($query_params['sort_dir']) . ($query_params['sort'] === 'relevance' ? '' : ', relevance desc') . ', poster_time DESC';
			// Grouping by topic id makes it return only one result per topic, so don't set that for in-topic searches
			if (empty($query_params['topic']))
				$query .= ' GROUP BY id_topic WITHIN GROUP ORDER BY ' . $sphinx_sort;
			$query .= ' ORDER BY ' . $sphinx_sort;

			$query .= ' LIMIT 0,' . (int) $modSettings['sphinx_max_results'];

			// Any limitations we need to add?
			if (!empty($modSettings['sphinx_max_results']) && (int) $modSettings['sphinx_max_results'] > 0)
				$query .= ' OPTION max_matches=' . (int) $modSettings['sphinx_max_results'];

			// Execute the search query.
			$request = $this->dbfunc_query($query, $mySphinx);

			// Can a connection to the daemon be made?
			if ($request === false)
			{
				// Just log the error.
				if ($this->dbfunc_error($mySphinx))
					log_error($this->dbfunc_error($mySphinx));
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
			$this->dbfunc_close($mySphinx);

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

		return $cached_results['total'];
	}

	/**
	 * Constructs a binary mode query to pass back to sphinx
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
			$addWords = $phrase ? array('"' . $cleanWords . '"') : preg_split('~ ~u', $cleanWords, NULL, PREG_SPLIT_NO_EMPTY);

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

			// AND is implied in a Sphinx Search
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

		// SMF only calls this search API when we delete, not recycle. So this will always be a remove.
		$query = '
			DELETE FROM smf_index
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

	/**
	 * Sphinx Database Support API: connect
	 *
	 * @access private
	 * @param string $host The sphinx search address, this will default to $modSettings['sphinx_searchd_server'].
	 * @param string $port The port Sphinx runs on, this will default to $modSettings['sphinxql_searchd_port'].
	 * @return resource
	 */
	private function dbfunc_connect(string $host = '', string $port = '')
	{
		global $modSettings, $txt;

		// Fill out our host and port if needed.
		if (empty($host))
			$host = $modSettings['sphinx_searchd_server'] == 'localhost' ? '127.0.0.1' : $modSettings['sphinx_searchd_server'];
		if (empty($port))
			$port = empty($modSettings['sphinxql_searchd_port']) ? 9306 : (int) $modSettings['sphinxql_searchd_port'];

		if ($this->db_type == 'mysqli')
		{
			$mySphinx = @mysqli_connect($host, '', '', '', $port);

			// Mysqli is never a resource, but an object.
			if (!is_object($mySphinx) || $mySphinx->connect_errno > 0)
			{
				loadLanguage('Errors');
				fatal_error($txt['error_no_search_daemon']);
			}			
		}
		else
		{
			// I tried to do this properly by changing error_reporting, but PHP ignores that. So surpress!
			$mySphinx = @mysql_connect($host . ':' . $port);

			if (!is_resource($mySphinx))
			{
				loadLanguage('Errors');
				fatal_error($txt['error_no_search_daemon']);
			}			
		}

		return $mySphinx;
	}
	/**
	 * Sphinx Database Support API: query
	 *
	 * @access private
	 * @param string $query The query to run.
	 * @param resource $mySphinx A SphinxQL connection resource.
	 * @return resource
	 */
	private function dbfunc_query(string $query, $mySphinx)
	{
		// MySQLI Procedural Style has the resource first then the query.
		if ($this->db_type == 'mysqli')
			return mysqli_query($mySphinx, $query);
		else
			return mysql_query($query, $mySphinx);
	}

	/**
	 * Sphinx Database Support API: num_rows
	 *
	 * @access private
	 * @param resource $mySphinx A SphinxQL request resource.
	 * @return int|string
	 */
	private function dbfunc_num_rows($mySphinx)
	{
		if ($this->db_type == 'mysqli')
			return mysqli_num_rows($mySphinx);
		else
			return mysql_num_rows($mySphinx);
	}

	/**
	 * Sphinx Database Support API: fetch_assoc
	 *
	 * @access private
	 * @param resource $mySphinx A SphinxQL request resource.
	 * @return array
	 */
	private function dbfunc_fetch_assoc($mySphinx)
	{
		if ($this->db_type == 'mysqli')
			return mysqli_fetch_assoc($mySphinx);
		else
			return mysql_fetch_assoc($mySphinx);
	}

	/**
	 * Sphinx Database Support API: free_result
	 *
	 * @access private
	 * @param resource $mySphinx A SphinxQL request resource.
	 * @return void
	 */
	private function dbfunc_free_result($mySphinx)
	{
		if ($this->db_type == 'mysqli')
			return mysqli_free_result($mySphinx);
		else
			return mysql_free_result($mySphinx);
	}

	/**
	 * Sphinx Database Support API: free_result
	 *
	 * @access private
	 * @param resource $mySphinx A SphinxQL connection resource.
	 * @return bool
	 */
	private function dbfunc_close($mySphinx)
	{
		if ($this->db_type == 'mysqli')
			return mysqli_close($mySphinx);
		else
			return mysql_close($mySphinx);
	}

	/**
	 * Sphinx Database Support API: error
	 *
	 * @access private
	 * @param resource $mySphinx A SphinxQL connection resource.
	 * @return string
	 */
	private function dbfunc_error($mySphinx)
	{
		if ($this->db_type == 'mysqli')
			return mysqli_error($mySphinx);
		else
			return mysql_error($mySphinx);
	}
}

/**
 * Callback to a template from our admin search settings page.
 * This is used to generate hints and links to generate the Sphinx
 * configuration file.
 *
 * @access public
 */
function template_callback_SMFAction_Sphinx_Hints()
{
	global $db_type, $scripturl, $txt, $modSettings;

	if (!isset($modSettings['sphinx_data_path'], $modSettings['sphinx_log_path']))
	{
		echo '
				<dt></dt>
				<dd>', $txt['sphinx_config_hints_save'], '</dd>';

		return;
	}

	// Ensure these exist.
	if (empty($modSettings['sphinx_conf_path']))
		$modSettings['sphinx_conf_path'] = '/etc/sphinxsearch';
	if (empty($modSettings['sphinx_bin_path']))
		$modSettings['sphinx_bin_path'] = '/usr/bin';

	echo '
				<dt></dt>
				<dd><a href="', $scripturl, '?action=admin;area=managesearch;sa=weights">', $txt['search_weights'], '</a></dd>
				<dd>[<a href="', $scripturl, '?action=admin;area=managesearch;sa=settings;generateConfig;view" target="_blank">', $txt['sphinx_view_config'], '</a> | <a href="', $scripturl, '?action=admin;area=managesearch;sa=settings;generateConfig">', $txt['sphinx_download_config'], '</a>] (', $txt['sphinx_config_hints_save'], ')</dd>
			</dl>';


	$message = '
		' . sprintf($txt['sphinx_config_hints_desc'], $modSettings['sphinx_data_path']) . '[pre]mkdir -p ' . $modSettings['sphinx_data_path'] . '
mkdir -p ' . $modSettings['sphinx_log_path'] . '
chmod a+w ' . $modSettings['sphinx_data_path'] . '
chmod a+w ' . $modSettings['sphinx_log_path'] . '[/pre]';

	// Add a extra step for postgresql.
	if ($db_type == 'postgresql')
		$message .= '
		[hr]
		' . $txt['sphinx_config_hints_pgsql_func'] . '
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
		' . $txt['sphinx_config_hints_index_start'] . '[pre]' . $modSettings['sphinx_bin_path'] . '/indexer --config ' . $modSettings['sphinx_conf_path'] . '/sphinx.conf --all
' . $modSettings['sphinx_bin_path'] . '/searchd --config ' . $modSettings['sphinx_conf_path'] . '/sphinx.conf[/pre]
		' . $txt['sphinx_config_hints_index_finish'] . '
		[hr]
		' . $txt['sphinx_config_hints_cron_start'] . '[pre]# search indexer
10 3 * * * ' . $modSettings['sphinx_bin_path'] . '/indexer --config ' . $modSettings['sphinx_conf_path'] . '/sphinx.conf --rotate smf_base_index
0 * * * * ' . $modSettings['sphinx_bin_path'] . '/indexer --config ' . $modSettings['sphinx_conf_path'] . '/sphinx.conf --rotate smf_delta_index[/pre]';

	// Print out our message.
	echo parse_bbc($message);

	echo '
					<dl class="settings">';
}


// This is the sphinx configuration file.
/**
 * The Sphinx generated configuration file.  We perform some checks and
 * calculation and then issue a download with the appropriate setup.
 *
 * @access public
 */
function generateSphinxConfig()
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

	$host = $modSettings['sphinx_searchd_server'] == 'localhost' ? '127.0.0.1' : $modSettings['sphinx_searchd_server'];

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
	header('ETag: ' . sha1('sphinx.conf' . time()));

	if (isset($_GET['view']))
		header('Content-Type: text/plain');
	else
	{
		header('Content-Type: ' . ($context['browser']['is_ie'] || $context['browser']['is_opera'] ? 'application/octetstream' : 'application/octet-stream'));
		header('Content-Disposition: attachment; filename="sphinx.conf"');
	}

	header('Cache-Control: max-age=' . (525600 * 60) . ', private');

	// At this point, we are generating the configuration file.
	echo '#
# Sphinx configuration file (sphinx.conf), configured for SMF 2.1
#
# By default the location of this file would probably be:
# ' . $modSettings['sphinx_conf_path'] . '/sphinx.conf

source smf_source
{
	type 		= ', $supported_db_type, '
	sql_host 	= ', $db_server, '
	sql_user 	= ', $db_user, '
	sql_pass 	= ', $db_passwd, '
	sql_db 		= ', $db_name, '
	sql_port 	= 3306', empty($db_character_set) ? '' : '
	sql_query_pre = SET NAMES ' . $db_character_set;

	// Thanks to TheStupidOne for pgsql queries.
	if ($db_type == 'pgsql')
		echo '
	sql_query_pre = \
		SELECT update_settings(\'sphinx_indexed_msg_until\', (SELECT MAX(id_msg) FROM PREFIX_messages))';
	else
		echo '
	sql_query_pre =	\
		REPLACE INTO ', $db_prefix, 'settings (variable, value) \
		SELECT \'sphinx_indexed_msg_until\', MAX(id_msg) \
		FROM ', $db_prefix, 'messages';

	echo '
	sql_query_range = \
		SELECT 1, value \
		FROM ', $db_prefix, 'settings \
		WHERE variable = \'sphinx_indexed_msg_until\'
	sql_range_step = 1000';

	// Thanks to TheStupidOne for pgsql queries.
	if ($db_type == 'pgsql')
		echo '
	sql_query =     \
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
	sql_query =	\
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
	sql_attr_uint = id_topic
	sql_attr_uint = id_board
	sql_attr_uint = id_member
	sql_attr_timestamp = poster_time
	sql_attr_timestamp = relevance
	sql_attr_timestamp = num_replies
}

source smf_delta_source : smf_source
{
	sql_query_pre = ', isset($db_character_set) ? 'SET NAMES ' . $db_character_set : '', '
	sql_query_range = \
		SELECT s1.value, s2.value \
		FROM ', $db_prefix, 'settings AS s1, ', $db_prefix, 'settings AS s2 \
		WHERE s1.variable = \'sphinx_indexed_msg_until\' \
			AND s2.variable = \'maxMsgID\'
}

index smf_base_index
{
	html_strip 		= 1
	source 			= smf_source
	path 			= ', $modSettings['sphinx_data_path'], '/smf_sphinx_base.index', empty($modSettings['sphinx_stopword_path']) ? '' : '
	stopwords 		= ' . $modSettings['sphinx_stopword_path'], '
	min_word_len 	= 2
	charset_table 	= 0..9, A..Z->a..z, _, a..z
}

index smf_delta_index : smf_base_index
{
	source 			= smf_delta_source
	path 			= ', $modSettings['sphinx_data_path'], '/smf_sphinx_delta.index
}

index smf_index
{
	type			= distributed
	local			= smf_base_index
	local			= smf_delta_index
}

indexer
{
	mem_limit 		= ', (int) $modSettings['sphinx_indexer_mem'], 'M
}

searchd
{';

	// This is for the non legacy QL version, which we are not going support at this time.
	//	listen 			= ', (int) $modSettings['sphinx_searchd_port'], '

	echo '
	listen 			= ', !empty($modSettings['sphinx_searchd_bind']) ? $host : '0.0.0.0', ':', (empty($modSettings['sphinxql_searchd_port']) ? 9306 : (int) $modSettings['sphinxql_searchd_port']), ':mysql41
	log 			= ', $modSettings['sphinx_log_path'], '/searchd.log
	query_log 		= ', $modSettings['sphinx_log_path'], '/query.log
	read_timeout 	= 5
	max_children 	= 30
	pid_file 		= ', $modSettings['sphinx_data_path'], '/searchd.pid
	binlog_path		= ', $modSettings['sphinx_data_path'], '
}';

die;
}