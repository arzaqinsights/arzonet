import re

with open('c:/laragon/www/email/resources/views/landing/partials/pricing.blade.php', 'r', encoding='utf-8') as f:
    home_content = f.read()

with open('c:/laragon/www/email/resources/views/landing/pricing.blade.php', 'r', encoding='utf-8') as f:
    pricing_content = f.read()

home_grid_match = re.search(r'@foreach\(\$plans as \$key => \$plan\)(.*?)@endforeach', home_content, re.DOTALL)
pricing_grid_match = re.search(r'@foreach\(\$plans as \$key => \$plan\)(.*?)@endforeach', pricing_content, re.DOTALL)

print("Home Grid Match:", bool(home_grid_match))
if home_grid_match:
    print("Home Grid Length:", len(home_grid_match.group(1)))

print("Pricing Grid Match:", bool(pricing_grid_match))
if pricing_grid_match:
    print("Pricing Grid Length:", len(pricing_grid_match.group(1)))
