# datinglibre-verotel-bundle

## Installation

In your Symfony applications's root directory, run:

    composer require datinglibre/datinglibre-verotel-bundle

Add the following line to your Symfony application `bundles.php` file:

    DatingLibre\VerotelBundle\DatingLibreVerotelBundle::class => ['all' => true]

Include the routes by creating the new file `config/routes/datinglibre_verotel_bundle.yaml` with the content below:

    _datinglibre_verotel_bundle:
        resource: '@DatingLibreVerotelBundle/Resources/config/routes.yaml'
        prefix: /

Update your `security.yml` file limiting calls to the webhook to Verotel IP addresses ([check these are correct](https://www.verotel.com/static/nats/proxy-ips.txt)):

     - { path: ^/webhook/verotel$, roles: PUBLIC_ACCESS, ips: [ 195.20.32.202/32, 89.185.232.210/32, 83.223.52.202/32 ] }

If you are using the bundle as part of the `DatingLibre` project, you will need to update `webservers.yml` to make sure these IP addresses are not rate limited:

    no_rate_limit_addresses:
      - 195.20.32.202/32
      - 89.185.232.210/32
      - 83.223.52.202/32

## Configuration

In the `config/packages` directory, create the following file

    datinglibre_verotel.yaml

Enter your shop ID and active signup:

    dating_libre_verotel:
        shopId: '123456'
        signupActive: true

Create a placeholder entry in `.env` to allow the kernel to load using the local environment:

    VEROTEL_SIGNATURE_KEY=

If you are using this bundle as part of DatingLibre then encrypt your Verotel signature key using `ansible-vault`:

    ansible-vault encrypt_string --vault-password-file=~/vault_password do4foskvmd

Enter the output into `deploy/inventories/production/group_vars/webservers.yml` under `verotel_signature_key`.

When you run Ansible it will:
1. decrypt this value (given you have the vault password)
2. enter it into a dynamically generated `env.local` file
3. upload this file to the server, which will override `.env`.

## Licence

Copyright 2020-2022 DatingLibre.

Permission is hereby granted, free of charge, to any person obtaining a copy of this software and associated documentation files (the "Software"), to deal in the Software without restriction, including without limitation the rights to use, copy, modify, merge, publish, distribute, sublicense, and/or sell copies of the Software, and to permit persons to whom the Software is furnished to do so, subject to the following conditions:

The above copyright notice and this permission notice shall be included in all copies or substantial portions of the Software.

THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.


