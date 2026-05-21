import difflib

# Read home page pricing partial
with open('c:/laragon/www/email/resources/views/landing/partials/pricing.blade.php', 'r', encoding='utf-8') as f:
    home_content = f.read()

# Read pricing page
with open('c:/laragon/www/email/resources/views/landing/pricing.blade.php', 'r', encoding='utf-8') as f:
    pricing_content = f.read()

# Let's extract the <section> block containing the plans from home
home_section_match = home_content
# Let's extract the first <section> block containing the plans from pricing page
import re
pricing_section_match = re.search(r'<!-- Base Tiers Section -->\s*<section.*?</section>', pricing_content, re.DOTALL)

if pricing_section_match:
    home_lines = [line.strip() for line in home_content.split('\n') if line.strip()]
    pricing_section = pricing_section_match.group(0)
    pricing_lines = [line.strip() for line in pricing_section.split('\n') if line.strip()]
    
    diff = difflib.unified_diff(
        home_lines,
        pricing_lines,
        fromfile='home_pricing_partial',
        tofile='pricing_page_base_section',
        lineterm=''
    )
    diff_output = '\n'.join(list(diff))
    print("Diff Length:", len(diff_output))
    print(diff_output[:1000])
else:
    print("Could not find base section in pricing page.")
