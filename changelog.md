# Changelog

This document contains a changelog of what's new and fixed in Alpacka. Check the tags and commits for more specific
information about each fixed issue. 

## v0.3.1, 2016-05-30
- Fix incorrect call to modTransliterate

## v0.3.0, 2016-05-23
- Add abstract class for object oriented snippets with a bit more flexibility. 

## v0.2.3, 2016-03-29
- Fix critical error when setResource is called repeatedly (e.g. iterating over items)

## v0.2.2, 2016-03-23
- Fix issue with incorrectly setting the current resource to $this->modx->resource

## v0.2.1, 2016-02-13
- Fix issue parsing template variables due to missing resourceIdentifier

## v0.2.0, 2016-01-28
- Make sure context specific settings are loaded into $service->config when setting the working context
- Make $service->pathVariables public

## v0.1.2, 2016-01-20
- Fix/add [[+resource]] path placeholder 

## v0.1.1, 2015-12-29
- Fix incorrect templatesPath (should be templates_path) config option in _getTplChunk
- Remove space in the connector_url config option

## v0.1.0, 2015-12-18
- First alpha release

## v0.0.1, 2015-12-18
- Alpha of the alpha (first release to packagist)
