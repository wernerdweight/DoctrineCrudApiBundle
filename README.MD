DoctrineCrudApiBundle
====================================

CRUD API powered by Doctrine mapping for Symfony

[![Build Status](https://travis-ci.org/wernerdweight/DoctrineCrudApiBundle.svg?branch=master)](https://travis-ci.org/wernerdweight/DoctrineCrudApiBundle)
[![Latest Stable Version](https://poser.pugx.org/wernerdweight/doctrine-crud-api-bundle/v/stable)](https://packagist.org/packages/wernerdweight/doctrine-crud-api-bundle)
[![Total Downloads](https://poser.pugx.org/wernerdweight/doctrine-crud-api-bundle/downloads)](https://packagist.org/packages/wernerdweight/doctrine-crud-api-bundle)
[![License](https://poser.pugx.org/wernerdweight/doctrine-crud-api-bundle/license)](https://packagist.org/packages/wernerdweight/doctrine-crud-api-bundle)


Installation
------------

### 1. Download using composer

```bash
composer require wernerdweight/doctrine-crud-api-bundle
```

### 2. Enable the bundle

Enable the bundle in your kernel:

```php
    <?php
    // config/bundles.php
    return [
        // ...
        WernerDweight\DoctrineCrudApiBundle\DoctrineCrudApiBundle::class => ['all' => true],
    ];
```

Configuration
------------

### 3. Adjust entity mapping
    
#### Available properties:

##### Accessible
Must be used to mark entity available for the API.

##### Listable
If used, the marked property can be retrieved when listing the entity. \
If used with `default=true`, the marked property will be retrieved when listing the entity without response structure specified (see below for response structure explanation).

##### Creatable
If used, the marked property can be set when creating the entity. \
If used with `nested=true`, the properties of marked property (if property is an entity or a collection of entities) can also be set when creating the entity.

##### Updatable
If used, the marked property can be set when updating the entity. \
If used with `nested=true`, the properties of marked property (if property is an entity or a collection of entities) can also be set when updating the entity.

##### Metadata
Allows to specify additional parameters of the property. \
If used with `type=entity|collection`, the respective type will be expected by the API (default type is deducted from ORM mapping). \
If used with `class=App\Some\Entity`, the respective class will be expected by the API (default class is deducted from ORM mapping). \
If used with `payload=["argument1", "argument2"]`, the arguments provided will be passed to the getter when retrieving the property.

**Warning: Entity must implement ApiEntityInterface to be available to the API!**

#### Example using annotations:

```php
use Doctrine\ORM\Mapping as ORM;
use WernerDweight\DoctrineCrudApiBundle\Entity\ApiEntityInterface;
use WernerDweight\DoctrineCrudApiBundle\Mapping\Annotation as WDS;

/**
* @ORM\Table(name="app_artist")
* @ORM\Entity(repositoryClass="App\Repository\ArtistRepository")
* @WDS\Accessible()
*/
class Artist implements ApiEntityInterface
{
   /**
    * @var string
    *
    * @ORM\Column(name="id", type="guid")
    * @ORM\Id
    * @ORM\GeneratedValue(strategy="UUID")
    */
   private $id;

   /**
    * @var string
    *
    * @ORM\Column(name="name", type="string", nullable=false)
    * @WDS\Listable(default=true)
    * @WDS\Creatable()
    * @WDS\Updatable()
    */
   private $name;

   /**
    * @var ArrayCollection|PersistentCollection
    *
    * @ORM\OneToMany(targetEntity="App\Entity\Track", mappedBy="artist")
    * @WDS\Listable(default=true)
    * @WDS\Creatable(nested=true)
    * @WDS\Updatable(nested=true)
    */
   private $tracks;

   ...

   /**
    * @return string
    */
   public function getId(): string
   {
       return $this->id;
   }

   ...
}
```

#### Example using XML:

```xml
<?xml version="1.0" encoding="utf-8"?>
<doctrine-mapping
    xmlns="http://doctrine-project.org/schemas/orm/doctrine-mapping"
    xmlns:wds="http://schemas.wds.blue/orm/doctrine-crud-api-bundle-mapping"
    xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
    xsi:schemaLocation="http://doctrine-project.org/schemas/orm/doctrine-mapping https://www.doctrine-project.org/schemas/orm/doctrine-mapping.xsd"
>
    <entity repository-class="App\Repository\ArtistRepository" name="App\Entity\Artist" table="app_artist">
        <wds:accessible/>
        <id name="id" type="guid">
            <generator strategy="UUID" />
        </id>
        <field name="name" type="string" nullable="false">
            <wds:listable default="true"/>
            <wds:creatable/>
            <wds:updatable/>
        </field>
        <one-to-many field="tracks" target-entity="App\Entity\Track" mapped-by="artist" fetch="LAZY">
            <wds:listable default="true"/>
            <wds:creatable nested="true"/>
            <wds:updatable nested="true"/>
        </one-to-many>
        ...
    </entity>
</doctrine-mapping>
```

Usage
------------

### Endpoints

* **`/{entity-name}/list/`** - lists entities of type *EntityName* (see below for filtering, ordering etc.);
* **`/{entity-name}/length/`** - returns the count of entities of type *EntityName* that would be listed for given criteria;
* **`/{entity-name}/detail/{id}/`** - returns entity of type *EntityName* with primary key *id*
* **`/{entity-name}/create/`** - creates entity of type *EntityName*
* **`/{entity-name}/update/{id}/`** - updates entity of type *EntityName* with primary key *id*
* **`/{entity-name}/delete/{id}/`** - deletes entity of type *EntityName* with primary key *id*

### Specifying response structure

*Available for `list`, `detail`, `create`, `update`.*

TODO:

### Filtering

*Available for `list`, `length`.*

TODO:

### Pagination

*Available for `list`, `length`.*

TODO:

### Ordering

*Available for `list`.*

TODO:

### Groupping

*Available for `list`, `length`.*

TODO:

### Specifying fields to modify

*Available for `create`, `update`.*

TODO:

### Events

TODO:

### Request examples

TODO:

License
-------
This bundle is under the MIT license. See the complete license in the root directiory of the bundle.
