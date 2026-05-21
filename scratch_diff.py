import re
import difflib

# Read home page pricing partial
with open('c:/laragon/www/email/resources/views/landing/partials/pricing.blade.php', 'r', encoding='utf-8') as f:
    home_content = f.read()

# Read pricing page
with open('c:/laragon/www/email/resources/views/landing/pricing.blade.php', 'r', encoding='utf-8') as f:
    pricing_content = f.read()

# Extract grid block from both
home_grid_match = re.search(r'@foreach\(\$plans as \$key => \$plan\)(.*?)@endforeach', home_content, re.DOTALL)
pricing_grid_match = re.search(r'@foreach\(\$plans as \$key => \$plan\)(.*?)@endforeach', pricing_content, re.DOTALL)

if home_grid_match and pricing_grid_match:
    home_grid = home_grid_match.group(1).strip()
    pricing_grid = pricing_grid_match.group(1).strip()
    
    # Let's clean up some obvious whitespace differences
    home_lines = [line.strip() for line in home_grid.split('\n') if line.strip()]
    pricing_lines = [line.strip() for line in pricing_grid.split('\n') if line.strip()]
    
    diff = difflib.unified_diff(
        home_lines,
        pricing_lines,
        fromfile='home_pricing_partial_grid',
        tofile='pricing_page_grid',
        lineterm=''
    )
    print('\n'.join(list(diff)[:50]))
else:
    print("Could not find grid matches in one or both files.")
