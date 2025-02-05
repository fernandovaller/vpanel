# vPanel

vPanel é um simples painel para administrar sites em localhost utilizando o servidor Apache. Ele
fornece uma interface web amigável para facilitar o gerenciamento de sites locais.

## 🚀 Funcionalidades

- Executar apenas em ambiantes de DEV!
- Listar sites configurados no Apache
- Adicionar, remover e gerenciar virtual hosts
- Configuração simplificada do banco de dados SQLite
- Interface intuitiva para administração de projetos locais

## 📦 Requisitos

- PHP 7.4+
- Composer
- Apache com suporte a virtual hosts
- SQLite3

## 📥 Instalação

Clone o repositório e instale as dependências:

```sh
git clone https://github.com/fernandovaller/vpanel.git
cd vPanel
composer install
```

## ⚙️ Configuração

Crie o arquivo de ambiente `.env.local` baseado no `.env`:

```sh
cp .env .env.local
```

Edite o `.env.local` e configure a variável `DATABASE_URL` para usar SQLite:

```env
MKCERT_PATH=/etc/ssl/mkcert/
APACHE_VIRTUAL_HOST_PATH=/etc/apache2/sites-available/
MYSQL_USER=
MYSQL_PASSWORD=
```

Crie a estrutura do banco de dados:

```sh
php bin/console doctrine:database:create
php bin/console doctrine:migrations:migrate
```

## 🔥 Executando o projeto

Inicie o servidor embutido do Symfony:

```sh
symfony server:start -d
```

Acesse no navegador:

```
http://127.0.0.1:8000
```

## 🛠 Permissões: Evitando Pedir Senha

`www-data` é o usuário padrão do Apache/PHP no Linux. Se estiver usando outro usuário, substitua.

```sh
# Edite o arquivo sudoers
sudo visudo

# Adicione a seguinte linha no final do arquivo
www-data ALL=(ALL) NOPASSWD
```

## 📜 Licença

Este projeto é distribuído sob a licença MIT. Veja o arquivo [LICENSE](LICENSE) para mais detalhes.

