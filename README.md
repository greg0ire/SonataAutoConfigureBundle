SonataAutoConfigureBundle
=========================

Tries to auto configure your admin classes so you don't have to.

[![PHP Version](https://img.shields.io/badge/php-%5E7.1-blue.svg)](https://img.shields.io/badge/php-%5E7.1-blue.svg)
[![Latest Stable Version](https://poser.pugx.org/kunicmarko/sonata-auto-configure-bundle/v/stable)](https://packagist.org/packages/kunicmarko/sonata-auto-configure-bundle)
[![Latest Unstable Version](https://poser.pugx.org/kunicmarko/sonata-auto-configure-bundle/v/unstable)](https://packagist.org/packages/kunicmarko/sonata-auto-configure-bundle)

[![Build Status](https://travis-ci.org/kunicmarko20/SonataAutoConfigureBundle.svg?branch=master)](https://travis-ci.org/kunicmarko20/SonataAutoConfigureBundle)
[![Coverage Status](https://coveralls.io/repos/github/kunicmarko20/SonataAutoConfigureBundle/badge.svg?branch=master)](https://coveralls.io/github/kunicmarko20/SonataAutoConfigureBundle?branch=master)

Documentation
-------------

* [Installation](#installation)
* [Configuration](#configuration)
* [How does it work](#how-does-it-work)
* [Annotation](#annotation)

## Installation

**1.**  Add dependency with composer

```bash
composer require kunicmarko/sonata-auto-configure-bundle
```

**2.** Register the bundle in your `bundles.php`

```php
return [
    //...
    KunicMarko\SonataAutoConfigureBundle\SonataAutoConfigureBundle::class => ['all' => true],
];
```

## Configuration

```yaml
sonata_auto_configure:
    admin:
        suffix: Admin
        manager_type: orm
    entity:
        namespaces:
            - { namespace: App\Entity, manager_type: orm }
    controller:
        suffix: Controller
        namespaces:
            - App\Controller\Admin
```

## How does it work

This bundle tries to guess some stuff about your admin class. You only have to create your admin
classes and be sure that admin folder is included in auto discovery and that autoconfigure is enabled.

This bundle will tag your admin classes with `sonata.admin`, then we find all admin classes and if
autoconfigure is enabled we take class name. If you defined a suffix in the config (by default it is
`Admin`) we remove it to get the name, so if you had `CategoryAdmin` we get `Category`.

After that we check if `AdminOption` annotation is present, annotation has more priority than our
guessing. If annotation is not defined or some of the values that are mandatory are not present
we still try to guess.

First we set the label and based on previous example it will be `Category`.

Then we set the admin code which will be the service id, it will be `admin.category`.

After we try to find Entity `Category` in the list of namespaces you defined (by default it is just
`App\Entity`). If the entity is not found an exception is thrown and you will probably need to use
annotation to define the entity. You can set `manager_type` per namespace.

By default we will take `manager_type` from annotation, if that is not present we will take it
from namespace definition. If you define entity in your annotation but not the `manager_type` then
we will take the manager type from bundle configuration that will be available as parameter a
`sonata_auto_configure.admin.manager_type`.

Then we try to guess a controller, same as for entity we try to guess it in the list of namespaces
but we add a suffix (as in most situation people name it `CategoryController`) that you can disable
in configuration. If there is no controller we leave it as `null` and sonata will add its default
controller.

And that is it. We have all the info we need for defining admin class, if you used some of the
other tag options when defining your admin class you will have to use Annotation or register
admin on your own with `autoconfigure: false` that would look like:

```yaml
App\Admin\CategoryAdmin:
    arguments: [~, App\Entity\Category, ~]
    autoconfigure: false
    tags:
        - { name: sonata.admin, manager_type: orm, label: Category }
    public: true
```

Since your admin class is autowired you can still use setter injection but you have to add an
`@required` annotation:

```php
/**
 * @required
 */
public function setSomeSerivce(SomeService $someService)
{
    $this->someService = $someService;
}
```

## Annotation

```php
<?php

namespace App\Entity;

use KunicMarko\SonataAutoConfigureBundle\Annotation as Sonata;
use App\Controller\Admin\CategoryController;
use App\Entity\Category;

/**
 * @Sonata\AdminOptions(
 *     label="Category",
 *     managerType="orm",
 *     group="Category",
 *     showInDashboard=true,
 *     keepOpen=true,
 *     onTop=true,
 *     icon="<i class='fa fa-user'></i>",
 *     labelTranslatorStrategy="sonata.admin.label.strategy.native",
 *     labelCatalogue="App",
 *     pagerType="simple",
 *     controller=CategoryController::class,
 *     serviceId="app.admin.category",
 *     entity=Category::class,
 *     adminCode="admin_code",
 * )
 */
class CategoryAdmin
{
}
```
