# Changelog

All Notable changes to `laravel-uploadable` will be documented in this file
# 5.4.0 - 2025-04-04
### CHANGE
- update package to laravel 12.x.
- update composer deps.

# 5.3.0 - 2024-06-21
### CHANGE
- Gestito suffix con nome del campo

# 5.2.0 - 2024-05-21
### CHANGE
- update package to laravel 11.x.
- update composer deps.

# 5.1.0 - 2023-03-21
### CHANGE
- update package to laravel 10.x.
- update composer deps.

# 5.0.0 - 2022-07-01
### CHANGE BREAK
- remove storage property from UploadOptions class and add getStorage() method to UploadOptions class
to resolve serialize model problem, because object Laravel Disk is not serializzable.

# 4.2.0 - 2022-04-05
### CHANGE
- update package to laravel 9.
- update composer deps.

## 4.1.1 - 2022-02-16
### FIX
- Fix issue with setStorageDisk

## 4.1.0 - 2021-10-30
### CHANGE
- added support for laravel 8

## 4.0.0 - 2019-06-24
### ADD
- support for Laravel 5.8 and php min vers are now >=7.1.x.
- support for Laravel File Storage (upload doesn't depend on public_path() anymore).

## 3.1.1 - 2019-04-24
### FIX
- forget to fire Event for upload successfull.

## 3.1.0 - 2019-04-24
### ADD
- Events for upload and delete upload successfull.
- Back compatibility to laravel 5.0 in composer.json.


## 3.0.2 - 2018-10-28
### CHANGE
- readme.md

## 3.0.1 - 2018-10-28
### FIX
- Travis

## 3.0.0 - 2018-10-28
### ADD
- Update to laravel 5.7
- Update composer.json.

## 2.0.2 - 2017-03-31

- FIX in checkIfNeedToDeleteOldFile() $oldValue variable is null.
Thanks to danielec.
See: https://github.com/padosoft/laravel-uploadable/issues/3

## 2.0.1 - 2017-01-07

- update swiftmailer/swiftmailer to fix Remote Code Execution vulnerability in v5.4.1
- exclude composer.lock from .gitignore

## 2.0.0 - 2016-09-13

- Big refactor
- Add unit test with about 100% code coverage!

## 1.0.0 - 2016-08-14

- Initial release
