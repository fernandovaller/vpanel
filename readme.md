# vPanel

vPanel é um simples painel para administrar sites em localhost utilizando o servidor Apache. Ele fornece uma interface web amigável para facilitar o gerenciamento de sites locais.

## 🚀 Funcionalidades

- Listar sites configurados no Apache
- Adicionar, remover e gerenciar virtual hosts
- Configuração simplificada do banco de dados SQLite
- Interface intuitiva para administração de projetos locais

## 📦 Requisitos

- PHP 8.1+
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
DATABASE_URL="sqlite:///%kernel.project_dir%/var/data.db"
```

Crie a estrutura do banco de dados:

```sh
php bin/console doctrine:database:create
php bin/console doctrine:migrations:migrate
```

## 🔥 Executando o projeto

Inicie o servidor embutido do Symfony:

```sh
symfony server:start
```

Acesse no navegador:

```
http://127.0.0.1:8000
```

## 🛠 Comandos úteis

Criar uma nova entidade:

```sh
php bin/console make:entity
```

Criar uma nova migração:

```sh
php bin/console make:migration
```

Executar migrações pendentes:

```sh
php bin/console doctrine:migrations:migrate
```

## 📜 Licença

Este projeto é distribuído sob a licença MIT. Veja o arquivo [LICENSE](LICENSE) para mais detalhes.

