# Bearweb template

This doc demostrates how the template syatem work and how to use / write template for Bearweb.

# Overview

Bearweb itself only stores resource and it is up to the template to decide how to consume the resource. Bearweb template can be divided into three categories:
- Page: to serve a HTML web page.
- object: to serve a file (object).
- API: to allow the client to submit data to server.

Bearweb uses 2-stage template design:
1. Bearweb will first __call__ the main template. Note the __call__ means template will be _jailed_ inside a function; so, the template cannot access variables used by Bearweb. However, Bearweb will provide variable ```$BW``` to allow the template to access:
   - ```$BW->site``` The request resource.
   - ```$BW->session``` The current session and transaction.
   - ```$BW->user``` The session user.
2. Then the main template will __include__ the secondary template. Note the secondary template can access main template's variables (any veriable decleared by the main template and the ```$BW``` variable provided by Bearweb).

All templates are saved in the template directory donated by config ```BW_Config::Site_TemplateDir``` in ```index.php```. Which main and secondary templates to use are determined by the resource ```string[2] $BW->site->template```.

When using the template, there is a file IO system call to load the template file. To reduce the system load, Bearweb will embed some main templates, and some main template will embed some of their children templates. When Bearweb invoke a main template and when a main template include a secondary template, they will;
1. Look the embedded templates.
2. If not found, check the template directory.
3. If not found, throw an ```BW_WebServerError``` error. This error will force Bearweb to use error page template to disply a HTML page showing the error detail.

# Page
Custom template

In most case, pages on the same site share the same header and footer but has distinct content in the main section. This is the idea of page template: it provides a programable common header and footer for all resources using the page template. Consider the following example:
```php
// template/page.php
<!DOCTYPE html>
<?php header('Content-Type: text/html'); ?>
<html>
	<head><title><?=$BW->site->title?></title></head>
	<body>
<?php if ($BW->site->template[1] == 'error'): ?>
		<h1><?=$BW->site->title ?? 'Error unknown'?></h1>
		<p><?=$BW->site->description ?? 'No detail description'?></p>
		Request ID: <span><?=$BW->session->tID?></span>
<?php elseif ($BW->site->template[1] == 'direct'): ?>
		<?=$BW->site->content?>
<?php elseif ($BW->site->template[1] == 'dosomething'): ?>
		...
<?php else: ?>
		<?php
			$template = BW_Config::Site_TemplateDir.'page_'.$BW->site->template[1].'.php';
			if (!file_exists($template)) throw new BW_WebServerError('Secondary page template not found: '.$BW->site->template[1], 500);
			include $template;
		?>
<?php endif; ?>
	</body>
</html>
```

The above template did a number of tasks:
1. Define the resource MIME type (text/html) for webpage.
2. Provide a HTML template to construct the webpage using the resource data.
3. Provide a set of built in templates. In this example, we have "error" for error info, "direct" for outputing ```$BW->site->content```, "dosomething" for executing some code.
4. Look for secondary template if the resource request one that is not included in the built-in set. (Note we add a "page_" prefix in the example code, this is not mandatory; however, the prefix helps us to keep track of secondary templates in the template directory.)

You may have multiple main templates for webpage, and you may use name other than "page" (page.php in template directory). This could help if your site has more than one style. However, there must be a main template named "page" (hence a file "page.php") and there must be a subtemplate named "error" (either embedded in "page" template or saved as "page_error.php" in template directory). Bearweb will use this template upon error.

# Object
Bearweb built-in template with 2 built-in subtemplates

Object template is used to output a non-webpage resource, such as image and JS script. It can also be used to directly output HTML file without using the common header and footer in the page template.

The object template will first send MIME type header saved in ```$BW->site->meta``` to indicate the resource type.

Next, the object template has 2 built-in subtemplates:
- If subtemplate is "blob", the value in ```$BW->site->content``` will be used.
- If subtemplate is "local", Bearweb will use the value in ```$BW->site->content``` as a filename. It will look into the resource directory (donated by config ```BW_Config::Site_TemplateDir``` in ```index.php```) for that file. If file existed (and the system have read permission of that file), Bearweb will output the content of that file; otherwise, it will throw a ```BW_WebServerError``` error.
- Otherwise, check template ```'object_'.$BW->site->template[1].'.php'``` in the template directory and try to execute it.

# API
Bearweb built-in template
Bearweb built-in template with 2 sets of built-in subtemplates (user API and resource API)

API is used to send data from client to server. One example is processing form submitted by user.

Bearweb API only use JSON as response (server --> client); however, it is up to the subtemplate to decide request (client --> server) type, some APIs uses JSON, some uses application/x-www-form-urlencoded or multipart/form-data.

If there is something on the server-side goes wrong (such as database error), the API execution will be interrupt and Bearweb will use error page template to display the error detail. Otherwise, Bearweb APIs returns a JSON data in the following format:
```JSON
{
	"sID": "Session ID $BW->session->sID",
	"tID": "Transaction ID $BW->session->tID",
	"sUser": "Session User ID $BW->session->sUser",
	"http": 200,
	"something": {
		"data1": "123",
		"data2": 456
	}
}
```
if success, or: 
```JSON
{
	"sID": "Session ID $BW->session->sID",
	"tID": "Transaction ID $BW->session->tID",
	"sUser": "Session User ID $BW->session->sUser",
	"http": 400,
	"Error": "Something goes wrong..."
}
```
if failed.
