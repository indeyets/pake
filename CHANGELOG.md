1.99.3:

* `pake_symlink()` will throw `pakeException` if not successful

1.99.2:

* Failed `pake_sh()` call will return application's exit-code via `pakeException`
* Better support for tasks defined in methods:
 * "default" task can be set via method
 * `pake help short-taskname` works for tasks defined in methods
* Fixed issue with parsing project's composer.json, while looking for custom `vendor-dir`

1.99.1:

* Added support for custom `vendor-dir` in composer-based project
