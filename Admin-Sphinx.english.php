<?php
// Version: 2.0; Admin-SphinxQL
global $helptxt;

$txt['search_index_sphinxql'] = 'SphinxQL';
$txt['search_index_sphinxql_desc'] = '3rd party Sphinx Search Engine.';

$txt['sphinx_download_config'] = 'Download Sphinx.conf';

$txt['sphinx_admin_title'] = 'Sphinx';
$txt['sphinx_server_config_tittle'] = 'Sphinx Server Config Settings';
$txt['sphinx_server_config_note'] = 'After changing these settings you must update your Sphinx configuration file';
$txt['sphinx_smf_sphinx_tittle'] = 'Sphinx SMF Settings';
$txt['sphinx_smf_tittle'] = 'SMF Settings';
$txt['sphinx_config_hints_title'] = 'Sphinx Server Configuration Hints';

$txt['sphinx_data_path'] = 'Index data path';
$txt['sphinx_data_path_subtext'] = 'This is the path that will be containing the search index files used by Sphinx.';

$txt['sphinx_log_path'] = 'Log path';
$txt['sphinx_log_path_subtext'] = 'Server path that will contain the log files created by Sphinx.';

$txt['sphinx_stopword_path'] = 'Stopword path';
$txt['sphinx_stopword_path_subtext'] = 'The server path to the stopword list (leave empty for no stopword list).';

$txt['sphinx_indexer_mem'] = 'Memory limit indexer';
$txt['sphinx_indexer_mem_subtext'] = 'The maximum amount of (RAM) memory the indexer is allowed to be using.';
$txt['sphinx_indexer_mem_postinput'] = 'MB';

$txt['sphinx_searchd_server'] = 'Sphinx server';
$txt['sphinx_searchd_server_subtext'] = 'Server the Sphinx search deamon resides on.';

$txt['sphinxql_searchd_port'] = 'Sphinx port';
$txt['sphinxql_searchd_port_subtext'] = 'Port on which the search deamon will listen.';

$txt['sphinx_max_results'] = 'Maximum # matches';
$txt['sphinx_max_results_subtext'] = 'Maximum amount of matches the search deamon will return.';

$txt['search_index'] = 'SMF Search API';
$txt['sphinx_smf_search_standard'] = 'Standard (Default)';
$txt['sphinx_smf_search_fulltext'] = 'Fulltext';
$txt['sphinx_smf_search_custom'] = 'Custom index';
$txt['sphinx_smf_search_sphinx'] = 'Sphinx (Legacy)';
$txt['sphinx_smf_search_sphinxql'] = 'SphinxQL';

$txt['sphinx_config_hints_save'] = 'Please save your configuration first.';
$txt['sphinx_config_hints_desc'] = 'Create directories for storing the indexes: %1$s';
$txt['sphinx_config_hints_pgsql_func'] = 'Execute the following the following statement on your PostgreSQL database';
$txt['sphinx_config_hints_index_start'] = 'It\'s time to create the indexes. Do not proceed with the next command if it fails.';
$txt['sphinx_config_hints_index_finish'] = 'If everything worked so far, congratulations, Sphinx has been installed and works! Next step is modifying SMF\'s search to work with Sphinx';
$txt['sphinx_config_hints_cron_start'] = 'In order to keep the full-text index up to date, you need to add a cron job that will update the index from time to time. The configuration file defines two indexes: <tt>smf_delta_index</tt>, an index that only stores the recent changes and can be called frequently.  <tt>smf_base_index</tt>, an index that stores the full database and should be called less frequently.<br />
Adding the following lines to /etc/crontab would let the index rebuild every day (at 3 am) and update the most recently changed messages each hour:';