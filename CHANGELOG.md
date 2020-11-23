# Changelog

All notable changes to `dykhuizen/laravel-datatables` will be documented in this file

## 2.0.0 - 2020-11-22

- Added `simplePaginateable($forcePagination = true)`
- Altered `paginateable` to have `$forcePagination` as a parameter
- Changed `simplePaginateable` and `paginateable` to take a $forcePagination parameter. If this value is false, the query will default to a `->get()` call. If true, the query will default to its respective pagination function.
- Added `$maxPerPage` to `Paginateable` to prevent dos-like queries.
