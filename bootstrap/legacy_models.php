<?php

/*
 * Legacy Eloquent models declared with lower-case class names matching their
 * file names: company/contact/deal/staff. Loads of code (models, blades,
 * seeders) still reference them PascalCased — Company::class,
 * use App\Models\Contact, etc.
 *
 * Windows' case-insensitive filesystem hid the mismatch. On Linux the
 * autoloader receives the literal "App\Models\Company", misses the class map
 * (whose key is "App\Models\company"), falls back to PSR-4, looks for
 * Company.php, finds nothing, and fatals with "Class not found" (a 500 on the
 * CEO dashboard, contacts pages, anything touching these relations).
 *
 * An spl_autoload_register shim can't fix it: PHP's autoload recursion guard
 * is case-insensitive, so calling class_exists('App\Models\company') while
 * already inside the autoload for 'App\Models\Company' is refused as a
 * self-reference and the file never loads.
 *
 * Eager-loading the real classes here — at composer autoload-file inclusion
 * time, before any model reference runs — sidesteps it entirely: once
 * `class company` (etc.) is declared, every casing resolves case-insensitively
 * with no autoload call at all. Cost is four class loads per request; the
 * Eloquent base model is always loaded anyway.
 */

class_exists(\App\Models\company::class);
class_exists(\App\Models\contact::class);
class_exists(\App\Models\deal::class);
class_exists(\App\Models\staff::class);
