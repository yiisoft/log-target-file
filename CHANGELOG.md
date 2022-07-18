# Yii Log - File Target Change Log

## 1.1.1 under development

- Bug #38: Drop `rotateByCopy` and make it the default (@DplusG)
- Bug #43: Add `ext-zlib` to composer requirements (@DplusG)
- Enh #40: Add support for `yiisoft/files` of version `^2.0` (@DplusG)

## 1.1.0 May 23, 2022

- Chg #36: Raise the minimum `yiisoft/log` version to `^2.0` and the minimum PHP version to 8.0 (@rustamwin)

## 1.0.4 August 26, 2021

- Bug #35: Remove `Psr\Log\LoggerInterface` definition from configuration for using multiple targets to application (@devanych)

## 1.0.3 April 13, 2021

- Chg: Adjust config for yiisoft/factory changes (@vjik, @samdark)

## 1.0.2 March 23, 2021

- Chg: Adjust config for new config plugin (@samdark)

## 1.0.1 February 22, 2021

- Chg #29: Replace the default maximum file size for file rotation to `10` megabytes in `params.php` (@devanych)

## 1.0.0 February 11, 2021

Initial release.
