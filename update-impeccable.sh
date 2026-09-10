#!/bin/sh
# Update the Impeccable skill in this project and in the user-level (global)
# harness at $HOME.
#
# Impeccable ships its own native CLI next to the skill
# (.claude/skills/impeccable/scripts/impeccable), so no Node or npm is needed.
# This script just finds a usable launcher and drives `impeccable update` for
# each scope.
#
# Usage: ./update-impeccable.sh [--project | --global] [-f] [-h]

set -eu

PROJECT_DIR=$(CDPATH= cd -- "$(dirname -- "$0")" && pwd)
GLOBAL_HOME=${IMPECCABLE_GLOBAL_HOME:-${HOME:-/home/jfsenechal}}

do_project=1
do_global=1
force=0

usage() {
  cat <<'USAGE'
Usage: ./update-impeccable.sh [options]

Updates the Impeccable skill for this project and for the user-level install.

Options:
  --project, --project-only   Only update the project installation
  --global, --user            Only update the user-level installation
  -f, --force                 Replace installed skill files; on a failed
                              update, fall back to a forced reinstall
  -h, --help                  Show this help

Environment:
  IMPECCABLE_GLOBAL_HOME      Home directory holding the global install
                              (default: $HOME)
USAGE
}

while [ $# -gt 0 ]; do
  case "$1" in
    --project|--project-only) do_global=0 ;;
    --global|--global-only|--user) do_project=0 ;;
    -f|--force) force=1 ;;
    -h|--help) usage; exit 0 ;;
    *) printf 'update-impeccable: unknown option: %s\n\n' "$1" >&2; usage >&2; exit 2 ;;
  esac
  shift
done

# First executable launcher among the candidates, else `impeccable` on PATH.
find_cli() {
  for candidate in "$@"; do
    if [ -n "$candidate" ] && [ -x "$candidate" ]; then
      printf '%s\n' "$candidate"
      return 0
    fi
  done
  if command -v impeccable >/dev/null 2>&1; then
    command -v impeccable
    return 0
  fi
  return 1
}

# update_scope <project|global> <cwd> <launcher...>
# Runs `impeccable update`; on failure, either reinstalls (with -f) or prints
# the command to run by hand. The launcher derives IMPECCABLE_SKILL_DIR from
# its own location, so let it: a stale value would point at the wrong skill.
update_scope() {
  scope=$1
  workdir=$2
  shift 2

  if ! cli=$(find_cli "$@"); then
    printf '\n!! No impeccable CLI found for the %s scope.\n' "$scope" >&2
    printf '   Install it first: https://impeccable.style/docs/\n' >&2
    return 1
  fi

  printf '\n== Updating Impeccable (%s) using %s\n' "$scope" "$cli"

  set -- update "--$scope" -y
  if [ "$force" -eq 1 ]; then
    set -- "$@" --force
  fi

  unset IMPECCABLE_SKILL_DIR || true
  if [ "$scope" = global ]; then
    scope_home=$GLOBAL_HOME
  else
    scope_home=${HOME:-$GLOBAL_HOME}
  fi
  if (cd "$workdir" && HOME="$scope_home" "$cli" "$@"); then
    return 0
  fi

  printf '\n!! `impeccable update --%s` failed.\n' "$scope" >&2
  if [ "$force" -eq 1 ]; then
    printf '   Retrying as a forced reinstall...\n' >&2
    (cd "$workdir" && HOME="$scope_home" "$cli" install "--$scope" -y --force)
    return $?
  fi
  printf '   An install predating the current layout cannot be updated in place.\n' >&2
  printf '   Re-run with -f to reinstall it, or run:\n' >&2
  printf '     %s install --%s -y --force\n' "$cli" "$scope" >&2
  return 1
}

status=0

if [ "$do_project" -eq 1 ]; then
  update_scope project "$PROJECT_DIR" \
    "$PROJECT_DIR/.claude/skills/impeccable/scripts/impeccable" \
    "$PROJECT_DIR/.agents/skills/impeccable/scripts/impeccable" \
    "$GLOBAL_HOME/.claude/skills/impeccable/scripts/impeccable" \
    "$GLOBAL_HOME/.agents/skills/impeccable/scripts/impeccable" || status=1
fi

if [ "$do_global" -eq 1 ]; then
  # Run from the home directory so no project install is picked up by accident.
  update_scope global "$GLOBAL_HOME" \
    "$GLOBAL_HOME/.claude/skills/impeccable/scripts/impeccable" \
    "$GLOBAL_HOME/.agents/skills/impeccable/scripts/impeccable" \
    "$PROJECT_DIR/.claude/skills/impeccable/scripts/impeccable" \
    "$PROJECT_DIR/.agents/skills/impeccable/scripts/impeccable" || status=1
fi

if [ "$status" -eq 0 ] && cli=$(find_cli \
  "$PROJECT_DIR/.claude/skills/impeccable/scripts/impeccable" \
  "$GLOBAL_HOME/.claude/skills/impeccable/scripts/impeccable"); then
  printf '\n== Verifying\n'
  (cd "$PROJECT_DIR" && "$cli" check) || true
fi

exit "$status"
