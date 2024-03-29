DoctrineCrudApiBundle
====================================

CRUD API powered by Doctrine mapping for Symfony

[![Build Status](https://app.travis-ci.com/wernerdweight/DoctrineCrudApiBundle.svg?branch=master)](https://app.travis-ci.com/wernerdweight/DoctrineCrudApiBundle)
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
If used with `payload=["argument1", "argument2"]`, the arguments provided will be passed to the getter when retrieving the property. \
Payload arguments can reference public services by using `@` prefix (e.g. `@request_stack.currentRequest.query.tagThreshold`).

##### Unmapped
Allows to specify additional fields that are not mapped to the database. \
This way, you can add custom fields to the API response (e.g. to synthetise data from multiple other fields).

**Warning: Entity must implement ApiEntityInterface to be available to the API!**

#### Example using attributes:

```php
use Doctrine\ORM\Mapping as ORM;
use WernerDweight\DoctrineCrudApiBundle\Entity\ApiEntityInterface;
use WernerDweight\DoctrineCrudApiBundle\Mapping\Annotation as WDS;

#[ORM\Table(name: "app_artist")]
#[ORM\Entity(repositoryClass: "App\Repository\ArtistRepository")]
#[WDS\Accessible()]
class Artist implements ApiEntityInterface
{
   #[ORM\Column(name: "id", type: "guid")]
   #[ORM\Id]
   #[ORM\GeneratedValue(strategy: "UUID")]
   private string $id;

   #[ORM\Column(name: "name", type: "string", nullable: false)]
   #[WDS\Listable(default: true)]
   #[WDS\Creatable()]
   #[WDS\Updatable()]
   private string $name;

   /**
    * @var ArrayCollection|PersistentCollection
    */
   #[ORM\OneToMany(targetEntity: "App\Entity\Track", mappedBy: "artist")]
   #[WDS\Listable(default: true)]
   #[WDS\Creatable(nested: true)]
   #[WDS\Updatable(nested: true)]
   private $tracks;
   
   #[ORM\Column(name: "tags", type: "json", nullable: false, options: ["jsonb" => true])]
   #[WDS\Listable(default: true)]
   #[WDS\Metadata(payload: ["@request_stack.currentRequest.query.tagThreshold"])]
   private array $tags;
   
   #[WDS\Listable(default: true)]
   private string $primaryTag;  // this is unmapped, it doesn't have to be populated

   ...

   public function getId(): string
   {
       return $this->id;
   }
   
   ...
   
   public function getTags(?string $tagThreshold = null): array
   {
       // only return tags with score higher than the provided threshold
       if (null !== $tagThreshold) {
           return array_filter(
               $this->tags,
               fn (string $tag): bool => $tag['score'] >= (float)$tagThreshold
           );
       }
       return $this->tags;
   }
   
   public function getPrimaryTag(): string
   {
       // return the first tag (for simplicity, any logic can be here)
       return $this->tags[0]['value'] ?? '';
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
        <field name="tags" type="json" nullable="false">
            <options>
                <option name="jsonb">true</option>
                <option name="default">[]</option>
            </options>
            <wds:listable default="true" />
            <wds:metadata>
                <wds:payload>
                    <wds:argument>@request_stack.currentRequest.query.tagThreshold</wds:argument>
                </wds:payload>
            </wds:metadata>
        </field>
        <wds:unmapped name="primaryTag">
            <wds:listable default="true" />
        </wds:unmapped>
        ...
    </entity>
</doctrine-mapping>
```

Usage
------------

### Endpoints

* **[GET | POST] `/{entity-name}/list/`** - lists entities of type *EntityName* (see below for filtering, ordering etc.);
* **[GET] `/{entity-name}/length/`** - returns the count of entities of type *EntityName* that would be listed for given criteria;
* **[GET | POST] `/{entity-name}/detail/{id}/`** - returns entity of type *EntityName* with primary key *id*
* **[POST] `/{entity-name}/create/`** - creates entity of type *EntityName* (validation is executed on submitted data - **you need to add "api" validation group to your validation constraints**)
* **[POST] `/{entity-name}/update/{id}/`** - updates entity of type *EntityName* with primary key *id* (validation is executed on submitted data - **you need to add "api" validation group to your validation constraints**)
* **[DELETE] `/{entity-name}/delete/{id}/`** - deletes entity of type *EntityName* with primary key *id*

### Specifying response structure

*Available for `list`, `detail`, `create`, `update`.*

The API lets you decide the structure of the response. You should set some default structure using `Listable(default=true)` (see above) - properties marked as default will automatically be returned if no response structure is specified in the request. Furthermore, if you decide to specify response structure with nesting (you require a property that is a related entity or collection of entities - e.g. `artist.tracks`) but will not specify response structure for the nested entity, the defaults will be used to output the nested entity.

**WARNING: If you specify response structure, default properties on the specified level will be ignored! You have to specify all required fields in the request!**

Pass response structure with your request as query parameter like this:

```http request
GET /artist/list/?responseStructure%5Bname%5D=true&responseStructure%5Btracks%5D%5Btitle%5D=true&responseStructure%5Btracks%5D%5Bdifficulty%5D=true&responseStructure%5Btracks%5D%5Bgenre%5D%5Btitle%5D=true HTTP/1.1
Host: your-api-host.com
...
```

of like this:

```http request
POST /artist/list/ HTTP/1.1
Content-Type: application/json
Host: your-api-host.com
...
{
	"responseStructure": {
		"name": true,
		"tracks": {
			"title": true,
			"difficulty: true
		}
	}
}
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

You may reference part of the response structure to allow hierarchical output (e.g. tree structure) as follows:
```javascript
{
  responseStructure: {
    name: true,
    nodes: {
      name: true,
      depth: true,
      nodes: 'nodes'  // this line references its parent (nodes)
    }
  }
}
```
```javascript
{
  responseStructure: {
    company: {
      name: true,
      employees: {
        name: true,
        address: true,
        previousCompany: {
          dateLeft: true,
          ceo: 'company.employees'  // this line references the employees structure
        }
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
  filter: {
    logic: "and|or",
    conditions: [
      {
        // regular filter
        field: "path.to.field",
        operator: "eq|neq|...",
        value: "filtering value"
      },
      {
        // nested filter
        logic: "and|or",
        conditions: [ /*...*/ ]
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

or like this:

```http request
POST /artist/list/ HTTP/1.1
Content-Type: application/json
Host: your-api-host.com
...
{
  "filter": {
    "logic": "and",
    "conditions": [
      {
        "field": "this.name",
        "operator": "contains",
        "value": "radio"
      }
    ]
  }
}
```

For clarity, the filter parameter is as follows:
```javascript
{
  filter: {
    logic: "or",
    conditions: [
      {
        logic: "and",
        conditions: [
          {
            field: "this.name",
            operator: "contains",
            value: "radio"
          },
          {
            field: "this.tracks.title",
            operator: "contains",
            value: "creep"
          },
        ]
      },
      {
        logic: "and",
        conditions: [
          {
            field: "this.name",
            operator: "contains",
            value: "pink"
          },
          {
            field: "this.tracks.title",
            operator: "contains",
            value: "wish"
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
  orderBy: [
    {
      field: "path.to.field",
      direction: "asc|desc"
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
  orderBy: [
    {
      field: "name",
      direction: "desc"
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
  groupBy: [
    {
      field: "path.to.field",
      direction: "asc|desc",
      aggregates: [
        {
          function: "avg|count|min|max|sum",
          field: "path.to.field",
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
  groupBy: [
    {
      field: "this.difficulty",
      direction: "desc",
      aggregates: [
        {
          function: "count",
          field: "id"
        },
        {
          function: "sum",
          field: "difficulty"
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
  fields: [
    {
      title: "New Track",
      difficulty: 3,
      artist:{
        id: "e00ea3a8-bb91-4639-880c-10ce67a92987"
      },
      genre: {
        title: "New Genre"
      },
      chords: "Am      Em\nSome Lyrics\n"
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
  artist: "e00ea3a8-bb91-4639-880c-10ce67a92987",
  artist: {
    id: "e00ea3a8-bb91-4639-880c-10ce67a92987"
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

What you have to take care of yourself
------------

**Correct configuration of validation constraints:** \
Entity data is validated during create and update calls. API uses validator from symfony, so if you need your data to be validated, follow the [official symfony documentation](https://symfony.com/doc/current/validation.html#constraint-configuration). \
**WARNING: All validation constraints that should be used by the API must be assigned the "api" validation group!** (see [validation groups documentation](https://symfony.com/doc/current/validation/groups.html))

**Correct configuration of cascading:** \
The API will persist all newly created entities (including the nested ones), but it will not check for possible orphaned relations when deleting an item. You should correctly set cascading for any entity, that should be available for delete operation (see [doctrine documentation](https://www.doctrine-project.org/projects/doctrine-orm/en/2.6/reference/working-with-objects.html#removing-entities) for cascading configuration).

**CORS:** \
If your FE app (or any other app that communicates with the API) sends [preflight requests](https://developer.mozilla.org/en-US/docs/Glossary/Preflight_request), you have to handle their resolving (an easy option is to listen on `kernel.request` event and filter requests by method (`OPTIONS`) and controller (by implemented interface `DoctrineCrudApiControllerInterface`)). \
**HEADS UP: You can use [CORSBundle](https://github.com/wernerdweight/CORSBundle) to handle this use-case.**

**Access control:** \
You should protect your API with some credentials (API secret, client id, OAuth. ...). Since the methods are numerous and requirements may vary, the API does not force you to use a specific method. You can use [symfony security options](https://symfony.com/doc/current/security/guard_authentication.html), integrate a 3rd-party bundle, or use your custom solution. \
**HEADS UP: You can use [ApiAuthBundle](https://github.com/wernerdweight/ApiAuthBundle) to handle this use-case.**

Request examples
------------

*FYI: The data shown are taken from various chord libraries.*

**Basic listing:**
```bash
curl --request GET \
  --url http://your-api-host.com/artist/list/
```
```json
[
  {
    "name": "Radiohead",
    "tracks": [
      {
        "title": "Karma Police"
      },
      {
        "title": "Polyethylene"
      },
      {
        "title": "Creep"
      },
      {
        "title": "You and Whose Army"
      },
      {
        "title": "Lucky"
      }
    ]
  },
  {
    "name": "Pink Floyd",
    "tracks": [
      {
        "title": "Wish You Were Here"
      },
      {
        "title": "Time"
      },
      {
        "title": "Money"
      }
    ]
  }
]
```

**Partial response structure:**
```bash
curl --request GET \
  --url 'http://your-api-host.com/artist/list/?responseStructure%5Bname%5D=true&responseStructure%5Btracks%5D%5Btitle%5D=true&responseStructure%5Btracks%5D%5Bdifficulty%5D=true&responseStructure%5Btracks%5D%5Bgenre%5D=true'
```
```json
[
  {
    "name": "Radiohead",
    "tracks": [
      {
        "title": "Karma Police",
        "difficulty": 3,
        "genre": {
          "title": "Psychedelic Rock"
        }
      },
      {
        "title": "Polyethylene",
        "difficulty": 4,
        "genre": {
          "title": "Psychedelic Rock"
        }
      },
      {
        "title": "Creep",
        "difficulty": 1,
        "genre": {
          "title": "Psychedelic Rock"
        }
      },
      {
        "title": "You and Whose Army",
        "difficulty": 5,
        "genre": {
          "title": "Psychedelic Rock"
        }
      },
      {
        "title": "Lucky",
        "difficulty": 2,
        "genre": {
          "title": "Psychedelic Rock"
        }
      }
    ]
  },
  {
    "name": "Pink Floyd",
    "tracks": [
      {
        "title": "Wish You Were Here",
        "difficulty": 2,
        "genre": {
          "title": "Psychedelic Rock"
        }
      },
      {
        "title": "Time",
        "difficulty": 3,
        "genre": {
          "title": "Psychedelic Rock"
        }
      },
      {
        "title": "Money",
        "difficulty": 3,
        "genre": {
          "title": "Psychedelic Rock"
        }
      }
    ]
  }
]
```

**Full response structure specified:**
```bash
curl --request GET \
  --url 'http://your-api-host.com/artist/list/?responseStructure%5Bname%5D=true&responseStructure%5Btracks%5D%5Btitle%5D=true&responseStructure%5Btracks%5D%5Bdifficulty%5D=true&responseStructure%5Btracks%5D%5Bgenre%5D%5Btitle%5D=true'
```
```json
[
  {
    "name": "Radiohead",
    "tracks": [
      {
        "title": "Karma Police",
        "difficulty": 3,
        "genre": {
          "title": "Psychedelic Rock"
        }
      },
      {
        "title": "Polyethylene",
        "difficulty": 4,
        "genre": {
          "title": "Psychedelic Rock"
        }
      },
      {
        "title": "Creep",
        "difficulty": 1,
        "genre": {
          "title": "Psychedelic Rock"
        }
      },
      {
        "title": "You and Whose Army",
        "difficulty": 5,
        "genre": {
          "title": "Psychedelic Rock"
        }
      },
      {
        "title": "Lucky",
        "difficulty": 2,
        "genre": {
          "title": "Psychedelic Rock"
        }
      }
    ]
  },
  {
    "name": "Pink Floyd",
    "tracks": [
      {
        "title": "Wish You Were Here",
        "difficulty": 2,
        "genre": {
          "title": "Psychedelic Rock"
        }
      },
      {
        "title": "Time",
        "difficulty": 3,
        "genre": {
          "title": "Psychedelic Rock"
        }
      },
      {
        "title": "Money",
        "difficulty": 3,
        "genre": {
          "title": "Psychedelic Rock"
        }
      }
    ]
  }
]
```

**Artists like "radio" having track like "Creep" or artists like "pink" having track like "Wish":**
```bash
curl --request GET \
  --url 'http://your-api-host.com/artist/list/?filter%5Blogic%5D=or&filter%5Bconditions%5D%5B0%5D%5Blogic%5D=and&filter%5Bconditions%5D%5B0%5D%5Bconditions%5D%5B0%5D%5Bfield%5D=this.name&filter%5Bconditions%5D%5B0%5D%5Bconditions%5D%5B0%5D%5Boperator%5D=contains&filter%5Bconditions%5D%5B0%5D%5Bconditions%5D%5B0%5D%5Bvalue%5D=radio&filter%5Bconditions%5D%5B0%5D%5Bconditions%5D%5B1%5D%5Bfield%5D=this.tracks.title&filter%5Bconditions%5D%5B0%5D%5Bconditions%5D%5B1%5D%5Boperator%5D=contains&filter%5Bconditions%5D%5B0%5D%5Bconditions%5D%5B1%5D%5Bvalue%5D=creep&filter%5Bconditions%5D%5B1%5D%5Blogic%5D=and&filter%5Bconditions%5D%5B1%5D%5Bconditions%5D%5B0%5D%5Bfield%5D=this.name&filter%5Bconditions%5D%5B1%5D%5Bconditions%5D%5B0%5D%5Boperator%5D=contains&filter%5Bconditions%5D%5B1%5D%5Bconditions%5D%5B0%5D%5Bvalue%5D=pink&filter%5Bconditions%5D%5B1%5D%5Bconditions%5D%5B1%5D%5Bfield%5D=this.tracks.title&filter%5Bconditions%5D%5B1%5D%5Bconditions%5D%5B1%5D%5Boperator%5D=contains&filter%5Bconditions%5D%5B1%5D%5Bconditions%5D%5B1%5D%5Bvalue%5D=wish'
```
```json
[
  {
    "name": "Pink Floyd",
    "tracks": [
      {
        "title": "Wish You Were Here"
      },
      {
        "title": "Time"
      },
      {
        "title": "Money"
      }
    ]
  },
  {
    "name": "Radiohead",
    "tracks": [
      {
        "title": "Karma Police"
      },
      {
        "title": "Polyethylene"
      },
      {
        "title": "Creep"
      },
      {
        "title": "You and Whose Army"
      },
      {
        "title": "Lucky"
      }
    ]
  }
]
```

**Tracks by title descending:**
```bash
curl --request GET \
  --url 'http://your-api-host.com/track/list/?orderBy%5B0%5D%5Bfield%5D=title&orderBy%5B0%5D%5Bdirection%5D=desc'
```
```json
[
  {
    "title": "You and Whose Army"
  },
  {
    "title": "Wish You Were Here"
  },
  {
    "title": "Time"
  },
  {
    "title": "Polyethylene"
  },
  {
    "title": "Money"
  },
  {
    "title": "Lucky"
  },
  {
    "title": "Karma Police"
  },
  {
    "title": "Creep"
  }
]
```

**Groupping with aggregates, filtering, and response structure:**
```bash
curl --request GET \
  --url 'http://your-api-host.com/track/list/?groupBy%5B0%5D%5Bfield%5D=this.difficulty&groupBy%5B0%5D%5Bdirection%5D=desc&groupBy%5B0%5D%5Baggregates%5D%5B0%5D%5Bfunction%5D=count&groupBy%5B0%5D%5Baggregates%5D%5B0%5D%5Bfield%5D=id&groupBy%5B0%5D%5Baggregates%5D%5B1%5D%5Bfunction%5D=sum&groupBy%5B0%5D%5Baggregates%5D%5B1%5D%5Bfield%5D=difficulty&filter%5Blogic%5D=and&filter%5Bconditions%5D%5B0%5D%5Bfield%5D=this.artist.name&filter%5Bconditions%5D%5B0%5D%5Boperator%5D=eq&filter%5Bconditions%5D%5B0%5D%5Bvalue%5D=Pink%20Floyd&responseStructure%5Btitle%5D=true&responseStructure%5Bartist%5D%5Bname%5D=true'
```
```json
[
  {
    "aggregates": [
      {
        "id": {
          "count": 2
        }
      },
      {
        "difficulty": {
          "sum": 6
        }
      }
    ],
    "field": "difficulty",
    "value": "3",
    "hasGroups": false,
    "items": [
      {
        "title": "Time",
        "artist": {
          "name": "Pink Floyd"
        }
      },
      {
        "title": "Money",
        "artist": {
          "name": "Pink Floyd"
        }
      }
    ]
  },
  {
    "aggregates": [
      {
        "id": {
          "count": 1
        }
      },
      {
        "difficulty": {
          "sum": 2
        }
      }
    ],
    "field": "difficulty",
    "value": "2",
    "hasGroups": false,
    "items": [
      {
        "title": "Wish You Were Here",
        "artist": {
          "name": "Pink Floyd"
        }
      }
    ]
  }
]
```

**Pagination:**
```bash
curl --request GET \
  --url 'http://your-api-host.com/track/list/?orderBy%5B0%5D%5Bfield%5D=title&orderBy%5B0%5D%5Bdirection%5D=desc&offset=3&limit=2'
```
```json
[
  {
    "title": "Polyethylene"
  },
  {
    "title": "Money"
  }
]
```

**Length with groupping:**
```bash
curl --request GET \
  --url 'http://your-api-host.com/track/length/?groupBy%5B0%5D%5Bfield%5D=this.difficulty&groupBy%5B0%5D%5Bdirection%5D=desc&groupBy%5B0%5D%5Baggregates%5D%5B0%5D%5Bfunction%5D=count&groupBy%5B0%5D%5Baggregates%5D%5B0%5D%5Bfield%5D=id&groupBy%5B0%5D%5Baggregates%5D%5B1%5D%5Bfunction%5D=sum&groupBy%5B0%5D%5Baggregates%5D%5B1%5D%5Bfield%5D=difficulty&filter%5Blogic%5D=and&filter%5Bconditions%5D%5B0%5D%5Bfield%5D=this.artist.name&filter%5Bconditions%5D%5B0%5D%5Boperator%5D=eq&filter%5Bconditions%5D%5B0%5D%5Bvalue%5D=Pink%20Floyd&responseStructure%5Btitle%5D=true&responseStructure%5Bartist%5D%5Bname%5D=true'
```
```json
{
  "length": 2
}
```

**Detail with response structure:**
```bash
curl --request GET \
  --url 'http://your-api-host.com/artist/detail/e00ea3a8-bb91-4639-880c-10ce67a92987?responseStructure%5Bname%5D=true&responseStructure%5Btracks%5D%5Btitle%5D=true&responseStructure%5Btracks%5D%5Bdifficulty%5D=true&responseStructure%5Btracks%5D%5Bgenre%5D=true'
```
```json
{
  "name": "Radiohead",
  "tracks": [
    {
      "title": "Karma Police",
      "difficulty": 3,
      "genre": {
        "title": "Psychedelic Rock"
      }
    },
    {
      "title": "Polyethylene",
      "difficulty": 4,
      "genre": {
        "title": "Psychedelic Rock"
      }
    },
    {
      "title": "Creep",
      "difficulty": 1,
      "genre": {
        "title": "Psychedelic Rock"
      }
    },
    {
      "title": "You and Whose Army",
      "difficulty": 5,
      "genre": {
        "title": "Psychedelic Rock"
      }
    },
    {
      "title": "Lucky",
      "difficulty": 2,
      "genre": {
        "title": "Psychedelic Rock"
      }
    }
  ]
}
```

**Delete track:**
```bash
curl --request DELETE \
  --url http://your-api-host.com/track/delete/3cf54bb9-9e98-46fe-834c-298c4cb3763c
```
```json
{
  "title": "New Track"
}
```

**Update track:**
```bash
curl --request POST \
  --url 'http://your-api-host.com/track/update/c9e5c46d-94eb-4cbc-a778-992d115271d3?=' \
  --header 'content-type: multipart/form-data; boundary=---011000010111000001101001' \
  --form 'fields[title]=New Track (modified)' \
  --form 'fields[difficulty]=1' \
  --form 'fields[artist][id]=10401146-c83b-48dc-91f0-64abe93e84f4' \
  --form 'fields[genre][id]=fc3e17e3-96f2-4947-9567-ca053a557acd' \
  --form 'fields[chords]=C     Dm\nCompletely Different'
```
```json
{
  "title": "New Track (modified)"
}
```

**Update artist with existing nested tracks with existing nested genre:**
```bash
curl --request POST \
  --url 'http://your-api-host.com/artist/update/10401146-c83b-48dc-91f0-64abe93e84f4?=' \
  --header 'content-type: multipart/form-data; boundary=---011000010111000001101001' \
  --form 'fields[tracks][0][id]=9df932d7-b832-48ba-9884-a0cdecc017e9' \
  --form 'fields[tracks][0][title]=New Track 75' \
  --form 'fields[tracks][0][difficulty]=1' \
  --form 'fields[tracks][0][genre][title]=New Genre 81' \
  --form 'fields[tracks][1][title]=New Track 20' \
  --form 'fields[tracks][1][difficulty]=1' \
  --form 'fields[tracks][1][chords]=Em      Am\nSome other lyrics\n' \
  --form 'fields[tracks][1][genre][id]=8247ac0f-17bb-46b5-8f5e-b52fb77792b5'
```
```json
{
  "name": "New Artist (modified)",
  "tracks": [
    {
      "title": "New Track 75"
    },
    {
      "title": "New Track 20"
    }
  ]
}
```

**Create track:**
```bash
curl --request POST \
  --url 'http://your-api-host.com/track/create/?=' \
  --header 'content-type: multipart/form-data; boundary=---011000010111000001101001' \
  --form 'fields[title]=New Track' \
  --form 'fields[difficulty]=3' \
  --form 'fields[artist][id]=e00ea3a8-bb91-4639-880c-10ce67a92987' \
  --form 'fields[genre][id]=8247ac0f-17bb-46b5-8f5e-b52fb77792b5' \
  --form 'fields[chords]=Am      Em\nSome Lyrics\n'
```
```json
{
  "title": "New Track"
}
```

**Create artist with nested tracks with nested genre:**
```bash
curl --request POST \
  --url 'http://your-api-host.com/artist/create/?=' \
  --header 'content-type: multipart/form-data; boundary=---011000010111000001101001' \
  --form 'fields[name]=New Artist' \
  --form 'fields[tracks][0][title]=New Track 2' \
  --form 'fields[tracks][0][difficulty]=5' \
  --form 'fields[tracks][0][genre][title]=New Genre 2' \
  --form 'fields[tracks][0][chords]=Am      Em\nSome Lyrics\n' \
  --form 'fields[tracks][1][title]=New Track 3' \
  --form 'fields[tracks][1][difficulty]=1' \
  --form 'fields[tracks][1][chords]=Em      Am\nSome other lyrics\n' \
  --form 'fields[tracks][1][genre][id]=8247ac0f-17bb-46b5-8f5e-b52fb77792b5'
```
```json
{
  "name": "New Artist",
  "tracks": [
    {
      "title": "New Track 2"
    },
    {
      "title": "New Track 3"
    }
  ]
}
```

License
-------
This bundle is under the MIT license. See the complete license in the root directiory of the bundle.
