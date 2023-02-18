<?php
// Version: 2.0; Admin-ManticoreQL
global $helptxt;

$txt['search_index_manticore'] = 'Manticore';
$txt['search_index_manticore_desc'] = '3rd party Manticore Search Engine.';

$txt['manticore_download_config'] = 'Download manticore.conf';
$txt['manticore_view_config'] = 'View manticore.conf';

$txt['manticore_admin_title'] = 'Manticore';
$txt['manticore_server_config_tittle'] = 'Manticore Server Config Settings';
$txt['manticore_server_config_note'] = 'After changing these settings you must update your Manticore configuration file';
$txt['manticore_smf_manticore_tittle'] = 'Manticore SMF Settings';
$txt['manticore_smf_tittle'] = 'SMF Settings';
$txt['manticore_config_hints_title'] = 'Manticore Server Configuration Hints';

$txt['manticore_data_path'] = 'Index data path';
$txt['manticore_data_path_subtext'] = 'This is the path that will be containing the search index files used by Manticore.';

$txt['manticore_log_path'] = 'Log path';
$txt['manticore_log_path_subtext'] = 'Server path that will contain the log files created by Manticore.';

$txt['manticore_conf_path'] = 'Conf path';
$txt['manticore_conf_path_subtext'] = 'Server path that will contain the configuration file.';

$txt['manticore_pid_path'] = 'PID path';
$txt['manticore_pid_path_subtext'] = 'Server path that will contain the searchd process information.';

$txt['manticore_bin_path'] = 'Bin path';
$txt['manticore_bin_path_subtext'] = 'Server path that will contain the searchd binary.';

$txt['manticore_stopword_path'] = 'Stopword path';
$txt['manticore_stopword_path_subtext'] = 'The server path to the stopword list (leave empty for no stopword list).';

$txt['manticore_indexer_mem'] = 'Memory limit indexer';
$txt['manticore_indexer_mem_subtext'] = 'The maximum amount of (RAM) memory the indexer is allowed to be using.';
$txt['manticore_indexer_mem_postinput'] = 'MB';

$txt['manticore_searchd_server'] = 'Manticore server';
$txt['manticore_searchd_server_subtext'] = 'Server the Manticore search deamon resides on.';

$txt['manticore_searchd_bind'] = 'Bind daemon to interface';
$txt['manticore_searchd_bind_subtext'] = 'Binds the daemon to the Manticore server address.';

$txt['manticore_searchd_port'] = 'Manticore port';
$txt['manticore_searchd_port_subtext'] = 'Port on which the search deamon will listen.';

$txt['manticore_max_results'] = 'Maximum # matches';
$txt['manticore_max_results_subtext'] = 'Maximum amount of matches the search deamon will return.';

$txt['manticore_version'] = 'Manticore Version';
$txt['manticore_version_subtext'] = 'If the binary is accessible, it will attempt to auto detect, otherwise fall back to this.';

$txt['manticore_index_name'] = 'Index Name';
$txt['manticore_index_name_subtext'] = 'Typically only useful in a multi-forum environment, allows running mulitple indexes at once.';

$txt['manticore_config_hints_save'] = 'Please save your configuration first.';
$txt['manticore_config_hints_desc'] = 'Create directories for storing the indexes: %1$s';
$txt['manticore_config_hints_pgsql_func'] = 'Execute the following the following statement on your PostgreSQL database';
$txt['manticore_config_hints_index_start'] = 'It\'s time to create the indexes. Do not proceed with the next command if it fails.';
$txt['manticore_config_hints_index_finish'] = 'If everything worked so far, congratulations, Manticore has been installed and works! Next step is modifying SMF\'s search to work with Manticore';
$txt['manticore_config_hints_cron_start'] = 'In order to keep the full-text index up to date, you need to add a cron job that will update the index from time to time. The configuration file defines two indexes: <tt>smf_delta_index</tt>, an index that only stores the recent changes and can be called frequently.  <tt>smf_base_index</tt>, an index that stores the full database and should be called less frequently.<br />
Adding the following lines to /etc/crontab would let the index rebuild every day (at 3 am) and update the most recently changed messages each hour:';