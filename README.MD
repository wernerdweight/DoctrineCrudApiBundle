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

* **[GET] `/{entity-name}/list/`** - lists entities of type *EntityName* (see below for filtering, ordering etc.);
* **[GET] `/{entity-name}/length/`** - returns the count of entities of type *EntityName* that would be listed for given criteria;
* **[GET] `/{entity-name}/detail/{id}/`** - returns entity of type *EntityName* with primary key *id*
* **[POST] `/{entity-name}/create/`** - creates entity of type *EntityName*
* **[POST] `/{entity-name}/update/{id}/`** - updates entity of type *EntityName* with primary key *id*
* **[DELETE] `/{entity-name}/delete/{id}/`** - deletes entity of type *EntityName* with primary key *id*

### Specifying response structure

*Available for `list`, `detail`, `create`, `update`.*

The API lets you decide the structure of the response. You should set some default structure using `Listable(default=true)` (see above) - properties marked as default will automatically be returned if no response structure is specified in the request. Furthermore, if you decide to specify response structure with nesting (you require a property that is a related entity or collection of entities - e.g. `artist.tracks`) but will not specify response structure for the nested entity, the defaults for will be used to outpu the nested entity.

**WARNING: If you specify response structure, default properties on the specified level will be ignored! You have to specify all required fields in the request!**

Pass response structure with your request as query parameter like this:

```http request
GET /artist/list/?responseStructure%5Bname%5D=true&responseStructure%5Btracks%5D%5Btitle%5D=true&responseStructure%5Btracks%5D%5Bdifficulty%5D=true&responseStructure%5Btracks%5D%5Bgenre%5D%5Btitle%5D=true HTTP/1.1
Host: your-api-host.com
```

For clarity, the structure parameter is as follows:
```javascript
{
  responseStructure: {
    name: true,
    tracks: {
      title: true,
      difficulty: true,
      genre: {
        title: true
      }
    }
  }
}
```

### Filtering

*Available for `list`, `length`.*

The API lets you filter listed data using query parameter `filter`. The filter must respect entity relations (so if you want to filter by name of the artist (root entity in this example - root entity is always referenced as `this`), the key for the filter will be `this.name`, but if you want to filter by title of related tracks (a 1:N relation), the filter key will be `this.tracks.title`).

Filters can be nested and support `AND` and `OR` logic.

Following operators are supported:
* **eq** - is equal to (equivalent of `=` in SQL),
* **neq** - is not equal to (equivalent of `!=` in SQL),
* **gt** - is greater than (equivalent of `>` in SQL),
* **gte** - is greater than or equal (equivalent of `>=` in SQL),
* **gten** - is greater than or equal or NULL (equivalent of `>= OR IS NULL` in SQL),
* **lt** - is lower than (equivalent of `<` in SQL),
* **lte** - is lower than or equal (equivalent of `<=` in SQL),
* **begins** - begins with (equivalent of `LIKE '...%'` in SQL; textual properties only, case-insensitive),
* **contains** - contains (equivalent of `LIKE '%...%'` in SQL; textual properties only, case-insensitive),
* **not-contains** - does not contain (equivalent of `NOT LIKE '%...%'` in SQL; textual properties only, case-insensitive),
* **ends** - ends with (equivalent of `LIKE '%...'` in SQL; textual properties only, case-insensitive),
* **null** - is null (equivalent of `IS NULL` in SQL),
* **not-null** - is not null (equivalent of `IS NOT NULL` in SQL),
* **empty** - is empty (equivalent of `IS NULL OR = ''` in SQL; textual properties only),
* **not-empty** - is not empty (equivalent of `IS NOT NULL AND != ''` in SQL; textual properties only),
* **in** - is contained in (equivalent of `IN` in SQL).

Generally, the filter structure is as follows:
```javascript
{
  "filter": {
    "logic": "and|or",
    "conditions": [
      {
        // regular filter
        "field": "path.to.field",
        "operator": "eq|neq|...",
        "value": "filtering value"
      },
      {
        // nested filter
        "logic": "and|or",
        "conditions": [ /*...*/ ]
      },
      ...
    ]
  }
}
```

Pass filter settings with your request as query parameter like this:

```http request
GET /artist/list/?filter%5Blogic%5D=or&filter%5Bconditions%5D%5B0%5D%5Blogic%5D=and&filter%5Bconditions%5D%5B0%5D%5Bconditions%5D%5B0%5D%5Bfield%5D=this.name&filter%5Bconditions%5D%5B0%5D%5Bconditions%5D%5B0%5D%5Boperator%5D=contains&filter%5Bconditions%5D%5B0%5D%5Bconditions%5D%5B0%5D%5Bvalue%5D=radio&filter%5Bconditions%5D%5B0%5D%5Bconditions%5D%5B1%5D%5Bfield%5D=this.tracks.title&filter%5Bconditions%5D%5B0%5D%5Bconditions%5D%5B1%5D%5Boperator%5D=contains&filter%5Bconditions%5D%5B0%5D%5Bconditions%5D%5B1%5D%5Bvalue%5D=creep&filter%5Bconditions%5D%5B1%5D%5Blogic%5D=and&filter%5Bconditions%5D%5B1%5D%5Bconditions%5D%5B0%5D%5Bfield%5D=this.name&filter%5Bconditions%5D%5B1%5D%5Bconditions%5D%5B0%5D%5Boperator%5D=contains&filter%5Bconditions%5D%5B1%5D%5Bconditions%5D%5B0%5D%5Bvalue%5D=pink&filter%5Bconditions%5D%5B1%5D%5Bconditions%5D%5B1%5D%5Bfield%5D=this.tracks.title&filter%5Bconditions%5D%5B1%5D%5Bconditions%5D%5B1%5D%5Boperator%5D=contains&filter%5Bconditions%5D%5B1%5D%5Bconditions%5D%5B1%5D%5Bvalue%5D=wish HTTP/1.1
Host: your-api-host.com
```

For clarity, the filter parameter is as follows:
```javascript
{
  "filter": {
    "logic": "or",
    "conditions": [
      {
        "logic": "and",
        "conditions": [
          {
            "field": "this.name",
            "operator": "contains",
            "value": "radio"
          },
          {
            "field": "this.tracks.title",
            "operator": "contains",
            "value": "creep"
          },
        ]
      },
      {
        "logic": "and",
        "conditions": [
          {
            "field": "this.name",
            "operator": "contains",
            "value": "pink"
          },
          {
            "field": "this.tracks.title",
            "operator": "contains",
            "value": "wish"
          },
        ],
      }
    ]
  }
}
```

### Pagination

*Available for `list`, `length`.*

The API lets you paginate the listed data using a zero-indexed `offset` and `limit` parameters.

Pass pagination settings with your request as query parameters like this:

```http request
GET /artist/list/?offset=100&limit=20 HTTP/1.1
Host: your-api-host.com
```

### Ordering

*Available for `list`.*

The API lets you sort listed data using query parameter `orderBy`.

Generally, the orderBy structure is as follows (conditions will be applied in specified order):
```javascript
{
  "orderBy": [
    {
      "field": "path.to.field",
      "direction": "asc|desc"
    },
    ...
  ]
}
```

Pass ordering settings with your request as query parameter like this:

```http request
GET /artist/list/?orderBy%5B0%5D%5Bfield%5D=name&orderBy%5B0%5D%5Bdirection%5D=desc HTTP/1.1
Host: your-api-host.com
```

For clarity, the orderBy parameter is as follows:
```javascript
{
  "orderBy": [
    {
      "field": "name",
      "direction": "desc"
    }
  ]
}
```

### Groupping and aggregations

*Available for `list`, `length`.*

The API lets you list groupped data using query parameter `groupBy` with optional aggregates. Supported aggregate functions are `avg`, `count`, `min`, `max`, and `sum`. 

Generally, the groupBy structure is as follows (groupping will be applied in specified order; aggregates are optional):
```javascript
{
  "groupBy": [
    {
      "field": "path.to.field",
      "direction": "asc|desc",
      "aggregates": [
        {
          "function": "avg|count|min|max|sum",
          "field": "path.to.field",
        },
        ...
      ]
    },
    ...
  ]
}
```

Pass groupping settings with your request as query parameter like this:

```http request
GET /track/list/?groupBy%5B0%5D%5Bfield%5D=this.difficulty&groupBy%5B0%5D%5Bdirection%5D=desc&groupBy%5B0%5D%5Baggregates%5D%5B0%5D%5Bfunction%5D=count&groupBy%5B0%5D%5Baggregates%5D%5B0%5D%5Bfield%5D=id&groupBy%5B0%5D%5Baggregates%5D%5B1%5D%5Bfunction%5D=sum&groupBy%5B0%5D%5Baggregates%5D%5B1%5D%5Bfield%5D=difficulty HTTP/1.1
Host: your-api-host.com
```

For clarity, the groupBy parameter is as follows:
```javascript
{
  "groupBy": [
    {
      "field": "this.difficulty",
      "direction": "desc",
      "aggregates": [
        {
          "function": "count",
          "field": "id"
        },
        {
          "function": "sum",
          "field": "difficulty"
        }
      ]
    }
  ]
}
```

### Specifying fields to modify

*Available for `create`, `update`.*

When creating/updating an entity, the API needs to know, which properties to change. You must specify this using the `fields` request parameter.

Pass fields with your request as form data like this:

```http request
POST /track/create/?= HTTP/1.1
Content-Type: multipart/form-data; boundary=---011000010111000001101001
Host: your-api-host.com
Content-Length: 576

-----011000010111000001101001
Content-Disposition: form-data; name="fields[title]"
New Track
-----011000010111000001101001
Content-Disposition: form-data; name="fields[difficulty]"
3
-----011000010111000001101001
Content-Disposition: form-data; name="fields[artist][id]"
e00ea3a8-bb91-4639-880c-10ce67a92987
-----011000010111000001101001
Content-Disposition: form-data; name="fields[genre][title]"
New Genre
-----011000010111000001101001
Content-Disposition: form-data; name="fields[chords]"
Am      Em\nSome Lyrics\n
-----011000010111000001101001--
```

For clarity, the fields parameter is as follows:
```javascript
{
  "fields": [
    {
      "title": "New Track",
      "difficulty": 3,
      "artist":{
        "id": "e00ea3a8-bb91-4639-880c-10ce67a92987"
      },
      "genre": {
        "title": "New Genre"
      },
      "chords": "Am      Em\nSome Lyrics\n"
    }
  ]
}
```

**Please note** the difference between how `artist` and `genre` are specified. An ID is specified for artist - API will thus look for an existing artist with this ID (and will fail if it doesn't exist). The genre, on the other hand, doesn't have an ID specified, but (imagine) it is set as `Creatable(nested=true)`, co it can be created together with the track (the same applies to `Updatable(nested=true)`). The resulting track will therefore be assigned an existing artist and a newly created genre.

**NOTE:** If you specify the ID of the related entity, specifing any other fields for the related entity is a no-op.**

**NOTE:** Specifying the ID value right as a value for the related entity key is equivalent to specifying the ID value as a key under the related entity:

```javascript
{
  // these are equivalent
  "artist": "e00ea3a8-bb91-4639-880c-10ce67a92987",
  "artist": {
    "id": "e00ea3a8-bb91-4639-880c-10ce67a92987"
  }
}
```

### Events

The API dispatches events during certain operations, so you can hook in the process. For general info on how to use events, please consult the [official Symfony documentation](https://symfony.com/doc/current/event_dispatcher.html).

**PrePersistEvent (`wds.doctrine_crud_api_bundle.item.pre_persist`)** \
Issued during `create` endpoint call, right before the newly created entity is persisted. Contains the item being created.

**PostCreateEvent (`wds.doctrine_crud_api_bundle.item.post_create`)** \
Issued during `create` endpoint call, right after the newly created entity is flushed to the database. Contains the item being created.

**PreUpdateEvent (`wds.doctrine_crud_api_bundle.item.pre_update`)** \
Issued during `update` endpoint call, right before the updated entity is applied the data from request. Contains the item being updated.

**PostUpdateEvent (`wds.doctrine_crud_api_bundle.item.post_update`)** \
Issued during `update` endpoint call, right after the updated entity is flushed to the database. Contains the item being updated.

**PreDeleteEvent (`wds.doctrine_crud_api_bundle.item.pre_delete`)** \
Issued during `delete` endpoint call, right before the entity is deleted. Contains the item being deleted.

**PostDeleteEvent (`wds.doctrine_crud_api_bundle.item.post_delete`)** \
Issued during `delete` endpoint call, right after the entity is deleted. Contains the item being deleted.

**PreValidateEvent (`wds.doctrine_crud_api_bundle.item.pre_validate`)** \
Issued during `create` and `update` endpoint call, right before the validation is executed on the entity. Contains the item being created/updated.

**PreSetPropertyEvent (`wds.doctrine_crud_api_bundle.item.pre_set_property`)** \
Issued during `create` and `update` endpoint call, right before a value is applied to particular property of the item being created/updated. Contains the item being created/updated, the field being updated, and the value being applied.

Request examples
------------

TODO:

What you have to take care of yourself
------------

TODO:

License
-------
This bundle is under the MIT license. See the complete license in the root directiory of the bundle.
