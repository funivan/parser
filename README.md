Fiv/Parser
======

Flexible way for data scraping 
                    
[![Build Status](https://travis-ci.org/funivan/parser.svg?branch=dev-2.0)](https://travis-ci.org/funivan/parser)
[![GitHub version](https://badge.fury.io/gh/funivan%2Fparser.svg)](http://badge.fury.io/gh/funivan%2Fparser)

## Installation
`composer require fiv/parser:2.0.*`



## Fetch all href attributes  
```php

 $grabber = new \Fiv\Parser\Grabber();
 $links = $grabber->getHtml('http://funivan.com')->attribute('//a/@href')->getItems();
 
 print_r($links);
 
```

## Submit form  
```php

 $grabber = new \Fiv\Parser\Grabber();
 $page = $grabber->getHtml('http://funivan.com/admin/');
 
 $formData = array('name' => 'admin');
 $adminPage = $grabber->submitForm($formData, '//form[@id="login"]');
 
 $logoutLink = $adminPage->attribute('//a[@id="logout"]/@href')->getFirst();
 echo $logoutLink;
  
```



## Get page status  
```php

  $request = new \Fiv\Parser\Request();
  $request->get('http://funivan.com');
  $httpCode = $request->getInfo()->getHttpCode();
  echo $httpCode;

```

## Get page and store in local cache   
```php

  $request = new \Fiv\Parser\Request();
  $request->setCacheAdapter(new \Fiv\Parser\Cache\FileCache());

  
  # real request to server
  $page = $request->get("http://funivan.com/");

  # get from cache
  $cachePage = $request->get("http://funivan.com/");
  

```

