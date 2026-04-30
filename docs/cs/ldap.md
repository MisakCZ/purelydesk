# LDAP

Aplikace ověřuje uživatele proti standardnímu LDAP kompatibilnímu adresáři. Není natvrdo navázaná na eDirectory; eDirectory je jedna z podporovaných konfigurací vedle jiných LDAP serverů.

LDAP hesla se v aplikaci nikdy neukládají. Po úspěšném LDAP bindu se lokální tabulka `users` používá jako aplikační profil synchronizovaný z LDAPu.

## Průběh přihlášení

1. Uživatel zadá uživatelské jméno a heslo do přihlašovacího formuláře.
2. Aplikace provede bind pomocí read-only servisního účtu.
3. Uživatel je vyhledán pomocí `LDAP_USER_FILTER`.
4. Aplikace se pokusí provést bind jako nalezený uživatel podle jeho DN a zadaného hesla.
5. Při úspěchu se synchronizuje lokální uživatelský profil a role.
6. Spustí se Laravel session autentizace.

Používejte read-only LDAP servisní účet. Nepoužívejte doménového administrátora ani administrátora adresáře jako bind účet.

## Obecný LDAP příklad

```env
LDAP_ENABLED=true
LDAP_HOST=ldap.example.org
LDAP_PORT=389
LDAP_ENCRYPTION=starttls
LDAP_BASE_DN=dc=example,dc=org
LDAP_BIND_DN=cn=helpdesk-reader,ou=service-accounts,dc=example,dc=org
LDAP_BIND_PASSWORD=secret
LDAP_USER_FILTER=(&(objectClass=person)(uid={username}))
LDAP_USERNAME_ATTRIBUTE=uid
LDAP_EMAIL_ATTRIBUTE=mail
LDAP_DISPLAY_NAME_ATTRIBUTE=cn
LDAP_DISPLAY_NAME_ATTRIBUTES=displayName,fullName,cn
LDAP_UNIQUE_ID_ATTRIBUTE=entryUUID
LDAP_DEPARTMENT_ATTRIBUTE=department
```

## Příklad pro eDirectory

Tento příklad ponechává kód obecný a mění pouze konfiguraci:

```env
LDAP_ENABLED=true
LDAP_HOST=ldap.example.org
LDAP_PORT=636
LDAP_ENCRYPTION=ldaps
LDAP_BASE_DN=o=example
LDAP_BIND_DN=cn=helpdesk-reader,ou=service-accounts,o=example
LDAP_BIND_PASSWORD=secret
LDAP_USER_FILTER=(&(objectClass=Person)(uid={username}))
LDAP_USERNAME_ATTRIBUTE=uid
LDAP_EMAIL_ATTRIBUTE=mail
LDAP_DISPLAY_NAME_ATTRIBUTE=displayName
LDAP_DISPLAY_NAME_ATTRIBUTES=displayName,fullName,cn
LDAP_UNIQUE_ID_ATTRIBUTE=GUID
LDAP_DEPARTMENT_ATTRIBUTE=ou
LDAP_USER_GROUP_ATTRIBUTES=groupMembership,memberOf
```

## Unique ID a binární atributy

`LDAP_UNIQUE_ID_ATTRIBUTE` určuje stabilní LDAP atribut použitý jako lokální externí identita. Časté hodnoty:

- `entryUUID`
- `objectGUID`
- `GUID`
- `uid`

Některé LDAP atributy jsou binární. Typickým příkladem je eDirectory `GUID`. Pokud je nakonfigurovaný unique ID atribut binární nebo není validní UTF-8, aplikace ho bezpečně uloží do `users.external_id` jako:

```text
base64:<encoded-value>
```

Textové atributy zůstávají uložené jako čitelný text.

## Mapování rolí

Aplikace používá tři role:

- `user`
- `solver`
- `admin`

Skupiny pro role se nastavují pomocí:

```env
LDAP_ROLE_USER_GROUPS=
LDAP_ROLE_SOLVER_GROUPS=cn=helpdesk-solvers,ou=groups,dc=example,dc=org
LDAP_ROLE_ADMIN_GROUPS=cn=helpdesk-admins,ou=groups,dc=example,dc=org
LDAP_ALLOW_DEFAULT_USER_ROLE=true
```

Více DN skupin se odděluje středníkem, protože LDAP DN obsahují čárky:

```env
LDAP_ROLE_SOLVER_GROUPS=cn=helpdesk-solvers,ou=groups,dc=example,dc=org;cn=it-support,ou=groups,dc=example,dc=org
```

Admin má nejvyšší prioritu, solver druhou a user je výchozí role, pokud je `LDAP_ALLOW_DEFAULT_USER_ROLE=true`.

## Způsoby načítání skupin

Aplikace podporuje dva běžné modely skupin.

### Skupiny uvedené na uživatelském záznamu

Použijte atributy jako `memberOf` nebo `groupMembership`:

```env
LDAP_USER_GROUP_ATTRIBUTES=memberOf,groupMembership
LDAP_GROUPS_ENABLED=false
```

### Vyhledávání skupin

Skupiny se hledají pod group base DN a DN uživatele se porovnává přes member atribut:

```env
LDAP_GROUPS_ENABLED=true
LDAP_GROUP_BASE_DN=ou=groups,dc=example,dc=org
LDAP_GROUP_FILTER=(objectClass=groupOfNames)
LDAP_GROUP_MEMBER_ATTRIBUTE=member
```

Toto lze přizpůsobit i pro jiné objektové třídy skupin, pokud LDAP server používá jiné schéma.

## Šifrování

Podporované hodnoty:

- `none`
- `starttls`
- `ldaps`

V produkci používejte StartTLS nebo LDAPS. Operační systém musí důvěřovat certifikační autoritě, která vydala certifikát LDAP serveru. Pokud CA není důvěryhodná, LDAP TLS spojení může selhat i při jinak správném nastavení.

## Bezpečnostní poznámky

- Používejte read-only bind účet.
- Bind heslo ukládejte pouze v `.env`.
- Do Gitu neukládejte skutečné DN, hesla ani interní hostname.
- Před zapnutím produkčního přihlášení testujte proti neprodukčnímu LDAP adresáři.
