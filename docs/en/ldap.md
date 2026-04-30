# LDAP

The application authenticates users against a standard LDAP-compatible directory. It is not hard-coded to eDirectory; eDirectory is one supported configuration profile among other LDAP servers.

LDAP passwords are never stored in the application. After a successful LDAP bind, the local `users` table is used as an application profile synchronized from LDAP.

## Login Flow

1. The user enters username and password in the login form.
2. The application binds with a read-only service account.
3. The user entry is searched with `LDAP_USER_FILTER`.
4. The application attempts a bind as the found user DN using the submitted password.
5. On success, the local user profile and role membership are synchronized.
6. Laravel session authentication is started.

Use a read-only LDAP service account. Do not use a privileged domain or directory admin account as the bind user.

## Generic LDAP Example

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

## eDirectory Example

This example keeps the code generic and changes only configuration:

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

## Unique ID and Binary Attributes

`LDAP_UNIQUE_ID_ATTRIBUTE` identifies a stable LDAP attribute used as the local external identity. Common values include:

- `entryUUID`
- `objectGUID`
- `GUID`
- `uid`

Some LDAP attributes are binary. eDirectory `GUID` is a common example. If the configured unique ID is binary or not valid UTF-8, the application stores it safely in `users.external_id` as:

```text
base64:<encoded-value>
```

Text attributes remain stored as readable text.

## Role Mapping

The application uses three roles:

- `user`
- `solver`
- `admin`

Role groups are configured through:

```env
LDAP_ROLE_USER_GROUPS=
LDAP_ROLE_SOLVER_GROUPS=cn=helpdesk-solvers,ou=groups,dc=example,dc=org
LDAP_ROLE_ADMIN_GROUPS=cn=helpdesk-admins,ou=groups,dc=example,dc=org
LDAP_ALLOW_DEFAULT_USER_ROLE=true
```

Multiple group DNs are separated by semicolons because LDAP DNs contain commas:

```env
LDAP_ROLE_SOLVER_GROUPS=cn=helpdesk-solvers,ou=groups,dc=example,dc=org;cn=it-support,ou=groups,dc=example,dc=org
```

Admin has the highest priority, solver is second, and user is the default role if `LDAP_ALLOW_DEFAULT_USER_ROLE=true`.

## Group Lookup Modes

The application supports two common group models.

### Groups Listed on the User Entry

Use attributes such as `memberOf` or `groupMembership`:

```env
LDAP_USER_GROUP_ATTRIBUTES=memberOf,groupMembership
LDAP_GROUPS_ENABLED=false
```

### Group Search

Search groups under a group base DN and match the user's DN through a member attribute:

```env
LDAP_GROUPS_ENABLED=true
LDAP_GROUP_BASE_DN=ou=groups,dc=example,dc=org
LDAP_GROUP_FILTER=(objectClass=groupOfNames)
LDAP_GROUP_MEMBER_ATTRIBUTE=member
```

This can also be adapted for other group object classes if the LDAP server uses a different schema.

## Encryption

Supported values:

- `none`
- `starttls`
- `ldaps`

Use StartTLS or LDAPS in production. The operating system must trust the certificate authority that issued the LDAP server certificate. If the CA is not trusted, LDAP TLS connections can fail even when the LDAP settings are otherwise correct.

## Security Notes

- Use a read-only bind account.
- Store the bind password only in `.env`.
- Do not commit real DNs, passwords, or internal hostnames to Git.
- Test with a non-production LDAP directory before enabling production login.
