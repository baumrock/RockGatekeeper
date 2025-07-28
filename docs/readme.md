# RockGatekeeper

A lightweight ProcessWire module that provides simple password protection for your website using a configurable gatekeeper password.

## Overview

RockGatekeeper adds a basic authentication layer to your ProcessWire site. When enabled, it blocks access to all pages for guest users unless they provide the correct password via URL parameter.

## Features

- **Minimal footprint**: Only runs when gatekeeper is configured
- **Session-based**: Once authenticated, users stay logged in for the session
- **IP tracking**: Remembers the IP address of authenticated users
- **Clean URLs**: Automatically removes the password parameter from URLs after authentication
- **CLI safe**: Won't interfere with command-line operations

## Installation

1. Copy the `RockGatekeeper` folder to your `site/modules/` directory
2. Install the module in ProcessWire admin
3. Configure the gatekeeper password in your site config

## Configuration

Add the gatekeeper password to your site configuration:

```php
// In site/config.php
$config->gatekeeper = 'your-secret-password';
```

## Usage

### Basic Authentication

To access the protected site, users need to append the password as a URL parameter:

```
https://yoursite.com/?gatekeeper=your-secret-password
```

After successful authentication, users will be redirected to the same page without the password parameter, and they'll have access to the entire site for the duration of their session.
