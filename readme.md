# Sphinx for SMF

Sphinx for SMF is a customization that allows SMF to make use of the [Sphinx Search][lnk1] engine.  This is not designed for any forum with less than 300,000 messages.

SMF ships by default with the standard, full text and custom index options.  Although most forums will have no problems using these options, larger SMF forums will start to suffer performance degradation.  These performance issues are mostly related to what databases such as MySQL and Postgresql can handle for how the storage is designed for text based matches.  Sphinx solves this by sucking all messages into its own database designed to handle searching.

At this time, this API requires sphinx to update itself.  Future support may include having SMF inform Sphinx of updates to a message.

In order to use this, you must use the following or higher.
- SMF 2.0.11 or higher
    - Latest 2.0.x is recommended.
    - SMF 2.1 support has had very little testing.
- Supports MySQL and MySQLi functions
    - Initial Postgresql support was added but has had limited testing. 
- Tested with Sphinx Search 2.2.10 using the SphinxQL (not the API)
- Linux Kernel 3.0+.
    - This is mostly a recommendation as Sphinx is avaiiable from many package maintainers from a variety of linux flavors and can be compiled on.
    - Sphinx is supported on 2.4 and 2.6 version kernels and so is this customization.
- Any modern Linux distribution
    - Ubuntu 16.04
    - CentOS 6
    - Debian 8
- PHP 5.4
    - PHP 7.0 or higher recommended
    - Follow SMF release versioning for specifics of minimum and recommend versions.  This customization will support them.

[lnk1]: <https://sphinxsearch.com/>
