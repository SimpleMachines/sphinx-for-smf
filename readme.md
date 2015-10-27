# Sphinx for SMF

Sphinx for SMF is a customization that allows SMF to make use of the [Sphinx Search][lnk1] engine.  This is not designed for any forum with less than 300,000 messages.

SMF ships by default with the standard, full text and custom index options.  Although most forums will have no problems using these options, larger SMF forums will start to suffer performance degradation.  There are limits to what you can find in a second, the stress on the database and locked tables are only some of the problems you will run into with MySQL as your database grows.

In order to use this, you must use the following or higher.
- SMF 2.0.12 or higher
    - SMF 2.1 support has had very little testing 
- Supports MySQL and MySQLi functions
- Tested with Sphinx Search 2.2.10 using the SphinxQL (not the API)
- Any Linux distribution using kernel 3.0
    - Ubuntu 14.04
    - CentOS 5.9
    - Debian 7


[lnk1]: <http://sphinxsearch.com/>
