# Logs

Regenix does not use a third-party library for logging, for this we have written
our own implementation. 

## Basics

Regenix saves all log data to a special directory that is located at 
the `<root>/logs/<project>/` path. The directory `/logs/` should have
access for writing. Logs are not in an app directory, this is done for 
better separation of source code and generated data. 

> **IMPORTANT**: An application within Regenix contain no mutable data.
