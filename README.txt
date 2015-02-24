PHPDrive
----------------------------------------------------------------------

ABOUT

A simple SQLite-based textfile system with built-in versioning.  I'm
tired of fooling with fopen, fwrite, etc. when I need to save text to
a server.  PHPDrive allows me to read and write text to an SQLite
database using a filename key, go to previous versions, and delete the
file all without having to consider more than where the DB file goes.

PHPDrive was inspired by Dave Winer's nodeStorage but written in PHP
because that's my preferred web language.  nodeStorage can be found at
the following: https://github.com/scripting/nodeStorage

PHPDrive is presented as-is without warranty or support.

----------------------------------------------------------------------

TO DO

1. Include "mysqli" support.