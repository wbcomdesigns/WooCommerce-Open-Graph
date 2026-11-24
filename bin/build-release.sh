#!/usr/bin/env bash
# Build dist/<slug>-<version>.zip from the committed tree (git archive) minus .distignore,
# so the zip always equals the repo. Wbcom standard build for WordPress.org plugins.
set -euo pipefail
cd "$(dirname "$0")/.."

slug=woo-open-graph          # WordPress.org slug = zip folder. Locked by CI (wporg-folder-lock.yml).
main=open-graph-for-woocommerce.php           # main plugin file
version=$(grep -m1 -E '^[[:space:]]*\*?[[:space:]]*Version:' "$main" | grep -oE '[0-9]+(\.[0-9]+)+' | head -1)
[ -n "$version" ] || { echo "No Version: header in $main"; exit 1; }

work=$(mktemp -d); trap 'rm -rf "$work"' EXIT
mkdir -p "$work/$slug" dist
git archive HEAD | tar -x -C "$work/$slug"
if [ -f .distignore ]; then
	while IFS= read -r rule; do
		case "$rule" in ''|'#'*) continue ;; esac
		( cd "$work/$slug" && rm -rf -- ${rule#/} )
	done < .distignore
fi
# Standard dev files never ship, whatever .distignore says.
( cd "$work/$slug" && rm -rf -- .git* .github .wordpress-org .distignore .editorconfig .DS_Store \
	bin tests docs audit plan node_modules CLAUDE.md AGENTS.md BUILD*.md \
	composer.json composer.lock package.json package-lock.json [Gg]runtfile.js webpack.config.js \
	phpcs.xml* .phpcs.xml* phpstan.neon* phpunit.xml* .phpunit* )

zip="$PWD/dist/$slug-$version.zip"
rm -f "$zip"
# Deflate every entry: a PHP file stored uncompressed trips the store's upload firewall.
( cd "$work" && python3 -I -c 'import os,sys,zipfile
out,top=sys.argv[1],sys.argv[2]
with zipfile.ZipFile(out,"w",zipfile.ZIP_DEFLATED,compresslevel=9) as z:
    for r,d,f in os.walk(top):
        d.sort()
        for n in sorted(f): z.write(os.path.join(r,n))' "$zip" "$slug" )

# --- WordPress.org folder lock (Wbcom standard - do not edit) ----------------------
entries=$(unzip -Z1 "$zip")   # read the whole list once: no early-exit pipe, safe under pipefail
top=${entries%%/*}
[ "$top" = "$slug" ] || { echo "Zip folder '$top' is not the WordPress.org slug '$slug'"; exit 1; }
grep -qx "$slug/$main" <<<"$entries" || { echo "Main file $slug/$main missing from zip"; exit 1; }
[ "$(LC_ALL=C grep -a -c '<?php' "$zip" || true)" = 0 ] || { echo "Zip has plain-text PHP (upload firewall)"; exit 1; }
# -----------------------------------------------------------------------------------

leaks=$(unzip -Z1 "$zip" | grep -iE "^$slug/(audit|bin|docs|tests|node_modules|\.github)/|CLAUDE|\.map$|composer\.(json|lock)$|phpstan|phpcs" || true)
[ -z "$leaks" ] || { echo "Dev files in zip:"; echo "$leaks"; exit 1; }
echo "Built $zip ($(unzip -Z1 "$zip" | wc -l | tr -d ' ') entries)"
