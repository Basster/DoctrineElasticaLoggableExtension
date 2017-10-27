Basster/DoctrineElasticaLoggableExtension
========================

[![SensioLabsInsight](https://insight.sensiolabs.com/projects/8663efc0-a078-4f28-82f0-c88d8e57b4e6/mini.png)](https://insight.sensiolabs.com/projects/8663efc0-a078-4f28-82f0-c88d8e57b4e6)

This library utilizes Gedmo Loggable Doctrine Extension to persist the entity changes into elasticsearch via elastica.io.

To make it work in Symfony with Doctrine Extentions Bundle place the following config in your `services.yml`:

```yaml
# services.yml

services:
    # overwrite DoctrineExtensionsBundle default listener to inject the ElasticaLoggableListener 
    Stof\DoctrineExtensionsBundle\EventListener\LoggerListener:
      arguments:
        - '@Basster\ElasticaLoggable\Listener\ElasticaLoggableListener'
        - '@security.token_storage'
        - '@security.authorization_checker'
      public: true
      tags:
        - {name: kernel.event_subscriber}

    # register the ElasticaLoggableListener as a service in your application
    Basster\ElasticaLoggable\Listener\ElasticaLoggableListener:
      public: true
      arguments: ['@elastica.type.activity']
      calls:
        - [setAnnotationReader, ['@annotation_reader']]
      tags:
        - { name: doctrine.event_subscriber, connection: default }

    # overwrite doctrine extension service aliases
    stof_doctrine_extensions.event_listener.logger: '@Basster\ElasticaLoggable\Listener\ElasticaLoggableListener'
    stof_doctrine_extensions.listener.loggable: '@Basster\ElasticaLoggable\Listener\ElasticaLoggableListener'
```
