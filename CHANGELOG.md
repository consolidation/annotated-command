# Change Log


### 1.4.0 - 13 September 2016

- Add basic annotation hook capability, to allow hook functions to be attached to commands with arbitrary annotations.


### 1.3.0 - 8 September 2016

- Add ComandFileDiscovery::setSearchDepth(). The search depth applies to each search location, unless there are no search locations, in which case it applies to the base directory.


### 1.2.0 - 2 August 2016

- Support both the 2.x and 3.x versions of phpdocumentor/reflection-docblock.
- Support php 5.4.
- **Bug** Do not allow an @param docblock comment for the options to override the meaning of the options.


### 1.1.0 - 6 July 2016

- Introduce AnnotatedCommandFactory::createSelectedCommandsFromClassInfo() method.


### 1.0.0 - 20 May 2016

- First stable release.
