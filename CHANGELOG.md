# Changelog

All notable changes to PS Decompose Word are documented in this file.

## 0.1.9

- Improved render-time performance with request-local option caches, cheaper cache keys, and a DOM preflight for short content.

## 0.1.8

- Preferred server-inserted soft hyphens over browser automatic hyphenation when render-time processing is enabled.

## 0.1.7

- Removed editor-only assets, settings, and REST preview support.

## 0.1.6

- Added a selected-block editor sidebar preview that showed server-processed soft hyphen positions without changing saved content.

## 0.1.5

- Added optional block editor hyphenation preview that did not modify saved block content.

## 0.1.4

- Added TeX-pattern hyphenation fallback for supported locales using `org_heigl/hyphenator`.

## 0.1.3

- Prefilled Block types with common title, prose, list, quote, table, and layout blocks.

## 0.1.2

- Preserved original word casing for case-insensitive exception dictionary matches.

## 0.1.1

- Fixed block theme title wrapping and locale-prefixed shorthand exceptions.

## 0.1.0

- Added initial CSS hyphenation and render-time exception support.