# Sphinx for SMF

Sphinx for SMF is a customization that allows SMF to make use of the [Sphinx Search](https://sphinxsearch.com/) engine or [Manticore](https://manticoresearch.com/).  This is not designed for any forum with less than 300,000 messages.

SMF ships by default with the standard, full text and custom index options.  Although most forums will have no problems using these options, larger SMF forums will start to suffer performance degradation.  These performance issues are mostly related to what databases such as MySQL and PostgreSQL can handle for how the storage is designed for text based matches.  Sphinx solves this by sucking all messages into its own database designed to handle searching.

At this time, this API requires sphinx to update itself.  Future support may include having SMF inform Sphinx of updates to a message.

In order to use this, you must use the following or higher.
- SMF 2.0.x, 2.1.x or higher
    - Latest release for 2.0 and 2.1 branches is recommended.
- Supports MySQL (via MySQLi functions)
    - Initial PostgreSQL support was added but has had limited testing. 
- Sphinx or Manticore
    - Sphinx - Using SphinxQL
        - Tested with 2.2.10
        - Tested with 3.4.1 (Only supported on SMF 2.1 or higher)
    - Manticore - Using SphinxQL with Plain Index
        - Tested with 4.2.0 (Only supported on SMF 2.1 or higher)
- Any modern Linux distribution
- PHP 7.0
    - PHP 7.4 or higher recommended
    - Follow SMF release versioning for specifics of minimum and recommend versions.  This customization will support them.