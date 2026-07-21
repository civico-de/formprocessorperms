#!/usr/bin/env bash
# Real HTTP enforcement test (Standalone): an authx api_key user whose role
# lacks the processor permission must get an authorization error from the
# api3 REST endpoint; after adding the permission to the role, the call must
# pass the gate. Assumes tests/e2e/e2e.php ran first (creates processor
# e2e_fp with permission 'e2e fp perm').
#
# Usage (inside the container): tests/e2e/e2e-http-standalone.sh [base-url]
set -euo pipefail

BASE_URL="${1:-http://localhost}"
API_KEY="e2e0123456789abcdef0123456789abc" # exactly 32 chars (varchar(32))

cv ev '
$key = "'"$API_KEY"'";
$role = \Civi\Api4\Role::save(FALSE)->setMatch(["name"])->addRecord([
  "name" => "e2e_api", "label" => "E2E API", "is_active" => TRUE,
  "permissions" => ["access CiviCRM", "access AJAX API", "authenticate with api key"],
])->execute()->first();
$contact = \Civi\Api4\Contact::save(FALSE)->setMatch(["external_identifier"])->addRecord([
  "contact_type" => "Individual", "first_name" => "E2E", "last_name" => "Api",
  "external_identifier" => "e2e_api", "api_key" => $key,
])->execute()->first();
$existing = \Civi\Api4\User::get(FALSE)->addWhere("username", "=", "e2e_api")->execute()->first();
if (!$existing) {
  \Civi\Api4\User::create(FALSE)
    ->addValue("username", "e2e_api")
    ->addValue("uf_name", "e2e-api@example.org")
    ->addValue("contact_id", $contact["id"])
    ->addValue("password", "e2e-password-1")
    ->addValue("roles:name", ["e2e_api"])
    ->execute();
}
civicrm_api3("System", "flush", []);
echo "setup done\n";
'

call() {
  curl -s -X POST "$BASE_URL/civicrm/ajax/rest" \
    -H "X-Civi-Auth: Bearer $API_KEY" \
    --data 'entity=FormProcessor&action=e2e_fp&json=1'
}

echo "--- call without permission"
RESP="$(call)"
echo "$RESP"
if ! echo "$RESP" | grep -qiE 'authoriz|permission'; then
  echo "E2E-HTTP FAIL: call without permission was NOT rejected" >&2
  exit 1
fi
echo "rejected as expected"

echo "--- grant permission to role, call again"
cv ev '
$role = \Civi\Api4\Role::get(FALSE)->addWhere("name", "=", "e2e_api")->execute()->first();
$perms = $role["permissions"];
$perms[] = "e2e fp perm";
\Civi\Api4\Role::update(FALSE)->addWhere("id", "=", $role["id"])
  ->addValue("permissions", array_unique($perms))->execute();
civicrm_api3("System", "flush", []);
echo "granted\n";
'
RESP="$(call)"
echo "$RESP"
if echo "$RESP" | grep -qiE 'authoriz|API permission check failed'; then
  echo "E2E-HTTP FAIL: call WITH permission was still rejected" >&2
  exit 1
fi
echo "E2E-HTTP PASS"
