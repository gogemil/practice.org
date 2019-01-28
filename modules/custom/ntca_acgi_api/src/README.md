# ACGI API Documentation

This readme is only going to contain the usage of the API code ONLY.  The API itself is documented in the file titled "APIs for Web Redesign (Details)" and if it is not in the project structure, it should also be available on "P:\Clients\NTCA - Rural Broadband\Tech" or somewhere in that area.

We only have requested access to the following API endpoints:

* Authentication
* Get Employers
* Get Valid Roles
* Get Event Registration Info
* Get Event Info

They will be documented below:

# Endpoints

**A few notes:**

* For EACH endpoint, the array that is passed is the data that will be the entire XML document.
* The examples below only show 1 or 2 items in the class instantiation, however multiple can be used
* Setters and getters are NOT used, instead we use magic methods (php `__get()` and php `__set()`).  This way, we can ensure that ONLY attributes that are used with the API are used in code.

## Multiple Endpoints Notice

We for each API entpoint, we have access to 2 different site APIs.  This means that we have 10 endpoints that the API detects gracefully.  There is no need for the developer to be aware of the endpoint, but they will need to be aware to what site they're preforming queries against.

## Authentication

**NTCA**:
```php
use Drupal\ntca_acgi_api\ACGIRequest\NTCA as NTCA_API;
$request = new NTCA_API\Login([
	'username' => 'testanother',
	'password' => 'testanother'
]);
print_r($request->execute());
```

or

**FRS**:
```php
use Drupal\ntca_acgi_api\ACGIRequest\FRS as FRS_API;
$request = new FRS_API\Login([
	'username' => 'testanother',
	'password' => 'testanother'
]);
print_r($request->execute());
```

The response you will receive is an instance of `Drupal\ntca_acgi_api\Entity\Authentication` Object

## Get Employers

**NTCA**:
```php
use Drupal\ntca_acgi_api\ACGIRequest\NTCA as NTCA_API;
$request = new NTCA_API\GetEmployers([
	'cust-id' => '1123929'
]);
print_r($request->execute());
```

or

**FRS**:
```php
use Drupal\ntca_acgi_api\ACGIRequest\FRS as FRS_API;
$request = new FRS_API\GetEmployers([
	'cust-id' => '1123929'
]);
print_r($request->execute());
```

The response you will receive is an instance of `Drupal\ntca_acgi_api\Entity\Employmentlist` Object

## Get Valid Roles

**NTCA**:
```php
use Drupal\ntca_acgi_api\ACGIRequest\NTCA as NTCA_API;
$request = NTCA_API\GetValidRoles();
print_r($request->execute());
```

or

**FRS**:
```php
use Drupal\ntca_acgi_api\ACGIRequest\FRS as FRS_API;
$request = new FRS_API\GetValidRoles();
print_r($request->execute());
```

The response you will receive is an instance of `Drupal\ntca_acgi_api\Entity\Rolelist` Object

## Get Event Registration Info

**NTCA**:
```php
use Drupal\ntca_acgi_api\ACGIRequest\NTCA as NTCA_API;
$request = NTCA_API\GetEventRegInfo([
    'event-id' => 1313
]);
print_r($request->execute());
```

or

**FRS**:
```php
use Drupal\ntca_acgi_api\ACGIRequest\FRS as FRS_API;
$request = new FRS_API\GetEventRegInfo([
    'event-id' => 1313
]);
print_r($request->execute());
```

The response you will receive is an instance of `Drupal\ntca_acgi_api\Entity\Registrations` Object

## Get Event Info

**NTCA**:
```php
use Drupal\ntca_acgi_api\ACGIRequest\NTCA as NTCA_API;
$request = NTCA_API\GetEventInfo([
    'status' => NTCA_API\GetEventInfo::STATUS_ACTIVE
]);
print_r($request->execute());
```

or

**FRS**:
```php
use Drupal\ntca_acgi_api\ACGIRequest\FRS as FRS_API;
$request = new FRS_API\GetEventInfo([
    'status' => FRS_API\GetEventInfo::STATUS_ACTIVE
]);
print_r($request->execute());
```

The response you will receive is an instance of `Drupal\ntca_acgi_api\Entity\Eventlist` Object

**Ensuring Proper SSO**:

Once we get the session ID / user ID out of the remote system, we need to make sure the SSO with (online.)ntca.org systems is set. Here is how - we need to set four cookies:

| Cookie Name | Cookie Value | Description |
| --- | --- | --- | --- |
| SSISID | System generated hash code | When a user logs in, a system generated hash code is stored to be used as a session identifier |
| SSALOGIN | 'yes' | Indicates the customer has logged in via Self-Service |
| P_CUST_ID | Customer ID | Gets set to the end-user's customer ID |
| BREADCRUMB_SESSION_ID | System generated breadcrumb session ID | This is required in order to use customer-specific stored document substitution variables. |

Notes: 

* All cookies set are session specific cookies. They should not be created as permanent cookies for security reasons.
* All cookies must be set at the domain level (no subdomain), and the root or "/" path. Cookie names are case sensitive (so all caps).
* Also, this is the system parameter for switching to the Apex header/template exclusively for header/footer styling. SSA_USE_APEX_HDR_FTR
  Most will use this method anymore as it is simpler to maintain just one template.
  You can still use stored documents inside the page.
  