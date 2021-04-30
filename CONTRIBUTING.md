# Contributing

Please just try to keep the same style in pull requests.

Indentation style is [K&R 1TBS](https://en.wikipedia.org/wiki/Indentation_style#Variant:_1TBS_(OTBS))

## Software Info

The Bootstrapper for duppy loads all the required starting services and builders for the application.
It handles lazy dependency injection, so it's easy to know where to get objects and makes it easier to test.

Services are an abstract class that allow creation of helper functions that can be mocked in testing easily if needed.
On top of that they serve temporary values per-request like user authentication, and handles caching.
To create a service create a class that extends *AbstractService* and services' singletons are created and managed when needed.

Duppy utilizes [Doctrine ORM](https://www.doctrine-project.org/projects/doctrine-orm/en/2.8/index.html) to manage data storage and database stuff.
It makes it extremely easy to get data from databases that PHP can understand.

File Builders allow the functionality to iterate any folder in the duppy `src/` directory to build a list of class names.
It allows filters and stuff and is used for the Router builder to allow easy Endpoint creation and Settings/SettingType creation

The Duppy API can run either on an Apache/Ngnix server (run once) or run on its own as a Workerman server.
This means running standalone it can run some important/highly used services early instead of on each request,
and improve performance even more. *Just keep this in mind when developing new services, mods, etc*