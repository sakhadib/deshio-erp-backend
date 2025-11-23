#!/usr/bin/env python3
"""
GitHub Issue Generator for Deshio ERP API Documentation

This script reads API documentation from a CSV file and generates GitHub issue titles 
and descriptions using OpenRouter's LLM models. It creates a new CSV with the original 
data plus generated issue descriptions.

Usage:
    python build.py --model <model_id>

Example:
    python build.py --model anthropic/claude-3.5-sonnet
"""

import argparse
import csv
import json
import os
import sys
from typing import Dict, List, Optional, Set
import pandas as pd
import requests
from dotenv import load_dotenv
import time
from colorama import init, Fore, Back, Style
from datetime import datetime

# Load environment variables
load_dotenv()

# Initialize colorama for cross-platform colored output
init(autoreset=True)

# Color utility functions
def print_success(message: str):
    """Print success message in green"""
    print(f"{Fore.GREEN}✓ {message}{Style.RESET_ALL}")

def print_error(message: str):
    """Print error message in red"""
    print(f"{Fore.RED}✗ {message}{Style.RESET_ALL}")

def print_warning(message: str):
    """Print warning message in yellow"""
    print(f"{Fore.YELLOW}⚠ {message}{Style.RESET_ALL}")

def print_info(message: str):
    """Print info message in blue"""
    print(f"{Fore.BLUE}ℹ {message}{Style.RESET_ALL}")

def print_header(message: str):
    """Print header message in cyan with decoration"""
    separator = "═" * len(message)
    print(f"\n{Fore.CYAN}{separator}")
    print(f"{Fore.CYAN}{message}")
    print(f"{Fore.CYAN}{separator}{Style.RESET_ALL}\n")

def print_progress(current: int, total: int, message: str):
    """Print progress with colored progress bar"""
    percentage = (current / total) * 100
    bar_length = 30
    filled_length = int(bar_length * current // total)
    bar = f"{Fore.GREEN}{'█' * filled_length}{Fore.WHITE}{'░' * (bar_length - filled_length)}{Style.RESET_ALL}"
    print(f"\r{Fore.MAGENTA}[{current:3d}/{total:3d}] {bar} {percentage:6.1f}% {Fore.CYAN}{message}{Style.RESET_ALL}", end="", flush=True)
    if current == total:
        print()  # New line when complete

PRIMARY_CONTEXT = '''
Deshio is a Laravel-based ERP for retail/omni with ~290 REST APIs over a normalized ERD covering catalog, orders, inventory, logistics, payments, RBAC, and audits.
Core entities: product/category/vendor with attributes (field/feature), media (product_image), barcodes, pricing (price_override), promotions; stores/branches; customers + tags/blacklist.
Inventory is batch-centric with optional per-unit items; supports reservations, adjustments, cycle counts, valuation, and ledgers.
Order domain: order/order_item states (pending→confirmed→fulfilled|cancelled), assignment to store, notes/attachments, promos, taxes, fraud flag.
Fulfillment: shipments (shipment/shipment_item) and inter-store dispatch (dispatch + tracking, scan events, POD) with transactional stock movements.
Returns: RMA (return/return_item), receive/restock/scrap, refunds, exchanges (exchange_order).
Payments: intent→payment capture/void; refund lifecycle; order.payment_status synchronization.
Accounting: transaction headers + ledger_entry lines for all movements; reversals; reconciliation/export.
Services: service/service_order with lifecycle and profitability.
Integrations: carriers & accounts, waybills/rates/tracking; webhooks with deliveries; notification templates (email/SMS).
Security & identity: user/role/permission with ability (role↔perm) and user_role (multi-role); sessions; email verification; password reset; TOTP MFA + backup codes; API keys.
Observability & compliance: audit_event, log, request-id, PII masking, health/metrics, queues, caching/etag.
Middleware plan: TLS/HSTS, CORS, RequestId, JSON limits, versioned content-negotiation, locale/tz, auth (JWT/session/API key), active user, email-verified, 2FA, RBAC & permission checks, branch scope, (optional) tenancy, throttles, idempotency, input sanitizer, domain guards (order/payment/dispatch/reservation/promo/override/cycle-count/fraud), upload mime guard, provider webhook signatures + replay defense, cache headers.
Reporting/analytics: revenue/orders/units by store/product/user, LTV/AOV, return/refund rates, SLA, dispatch performance, inventory aging/low stock, price change history, cycle count accuracy, promo impact, activity.
State machines drive legality of transitions for orders, order items, dispatch, shipments, returns, payments, cycle counts; conflicts return RFC7807 problem+json.
APIs include bulk import/export, search/autosuggest, attachments, reindex, backups/restore, feature flags, and system maintenance.
Data stored in UTC; soft deletes for archival; EAV for attributes; polymorphic notes/attachments; strict referential links across all junctions.
Design enables deterministic admin assignment for online orders, safe reservation/consumption, and consistent ledger posting on every stock/financial event.
'''


class OpenRouterClient:
    """Client for interacting with OpenRouter API"""
    
    def __init__(self, api_key: str):
        self.api_key = api_key
        self.base_url = "https://openrouter.ai/api/v1"
        self.headers = {
            "Authorization": f"Bearer {api_key}",
            "HTTP-Referer": "https://github.com/sakhadib/deshio-erp-backend",
            "X-Title": "Deshio ERP Issue Generator",
            "Content-Type": "application/json"
        }
    
    def generate_issue_description(self, model: str, api_data: Dict) -> Optional[Dict[str, str]]:
        """Generate GitHub issue title and description for an API endpoint"""
        
        prompt = f"""
You are a technical writer creating GitHub issues for API implementation. You MUST respond with valid JSON only.

Given the following API endpoint information from the Deshio ERP system, generate a comprehensive GitHub issue title and description for implementing this API endpoint.

Context about Deshio ERP:
{PRIMARY_CONTEXT}

API Details:
- Category: {api_data['category']}
- Title: {api_data['api_title']}
- Description: {api_data['api_description']}
- Route: {api_data['route']}
- HTTP Method: {api_data['Type']}
- Authentication: {api_data['Authentication_Type']}

Requirements:
1. Create a clear, concise GitHub issue title (max 80 characters)
2. Create a detailed issue description that includes ALL the provided information with proper formatting:
   - Brief overview of the API endpoint
   - API specifications (route, method, authentication)
   - Acceptance criteria with checkboxes
   - Technical requirements
   - Authentication/authorization requirements
   - Expected request/response format considerations
   - Any relevant business logic
   - Use \\n for line breaks to ensure proper formatting

You MUST respond with ONLY valid JSON in this exact format:
{{
    "title": "Your issue title here",
    "description": "## Overview\\n\\nDetailed description with proper line breaks...\\n\\n## API Specifications\\n\\n- **Route:** {api_data['route']}\\n- **Method:** {api_data['Type']}\\n- **Authentication:** {api_data['Authentication_Type']}\\n- **Category:** {api_data['category']}\\n\\n## Acceptance Criteria\\n\\n- [ ] Implement endpoint\\n- [ ] Add validation\\n- [ ] Write tests\\n\\n## Technical Requirements\\n\\n- Laravel controller and routes\\n- Input validation\\n- Proper error handling"
}}

Do NOT include any text before or after the JSON. Return ONLY the JSON object.
"""

        payload = {
            "model": model,
            "messages": [
                {
                    "role": "user",
                    "content": prompt
                }
            ],
            "temperature": 0.7,
            "max_tokens": 3000
        }
        
        try:
            response = requests.post(
                f"{self.base_url}/chat/completions",
                headers=self.headers,
                json=payload,
                timeout=30
            )
            response.raise_for_status()
            
            result = response.json()
            content = result['choices'][0]['message']['content'].strip()
            
            # Clean up the content to extract JSON if there's extra text
            if content.startswith('```json'):
                content = content[7:]
            if content.endswith('```'):
                content = content[:-3]
            content = content.strip()
            
            # Try to parse as JSON
            try:
                issue_data = json.loads(content)
                return {
                    "title": issue_data.get("title", f"Implement {api_data['api_title']} API"),
                    "description": issue_data.get("description", "Implementation details not generated")
                }
            except json.JSONDecodeError as e:
                print_warning(f"JSON parse error: {e}")
                print_warning(f"Raw content: {content[:200]}...")
                # Fallback: create structured description
                fallback_description = f"""## Overview\n\nImplement the {api_data['api_title']} API endpoint.\n\n## API Specifications\n\n- **Route:** {api_data['route']}\n- **Method:** {api_data['Type']}\n- **Authentication:** {api_data['Authentication_Type']}\n- **Category:** {api_data['category']}\n- **Description:** {api_data['api_description']}\n\n## Acceptance Criteria\n\n- [ ] Implement {api_data['Type']} endpoint at {api_data['route']}\n- [ ] Add proper authentication ({api_data['Authentication_Type']})\n- [ ] Implement input validation\n- [ ] Add error handling\n- [ ] Write unit tests\n- [ ] Update API documentation\n\n## Technical Requirements\n\n- Laravel controller and routes\n- Request validation\n- Response formatting\n- Error handling\n- Authentication middleware"""
                return {
                    "title": f"Implement {api_data['api_title']} API",
                    "description": fallback_description
                }
                
        except requests.exceptions.RequestException as e:
            print_error(f"API request failed: {e}")
            return None
        except (KeyError, IndexError) as e:
            print_error(f"Failed to parse API response: {e}")
            return None


def load_csv_data(file_path: str) -> List[Dict]:
    """Load API data from CSV file"""
    try:
        print_info(f"Loading CSV data from: {file_path}")
        df = pd.read_csv(file_path)
        print_success(f"Successfully loaded {len(df)} records")
        return df.to_dict('records')
    except FileNotFoundError:
        print_error(f"CSV file '{file_path}' not found")
        sys.exit(1)
    except Exception as e:
        print_error(f"Failed to read CSV file: {e}")
        sys.exit(1)


def get_processed_entries(output_path: str) -> Set[int]:
    """Get set of already processed API entry IDs from existing output file"""
    processed = set()
    try:
        if os.path.exists(output_path):
            df = pd.read_csv(output_path)
            # Get all existing IDs that have valid issue descriptions
            for _, row in df.iterrows():
                if 'issue_description' in row and pd.notna(row['issue_description']) and row['issue_description'] != "Failed to generate issue description":
                    processed.add(int(row['id']))
            print_info(f"Found {len(processed)} already processed entries")
    except Exception as e:
        print_warning(f"Could not read existing output file: {e}")
    return processed

def append_to_csv(issue_data: Dict, output_path: str, write_header: bool = False):
    """Append single issue entry to CSV file"""
    try:
        file_exists = os.path.exists(output_path)
        mode = 'w' if write_header and not file_exists else 'a'
        
        with open(output_path, mode, newline='', encoding='utf-8') as csvfile:
            # Include all original API data plus issue fields
            fieldnames = ['id', 'category', 'api_title', 'api_description', 'route', 'Type', 'Authentication_Type', 'issue_title', 'issue_description']
            writer = csv.DictWriter(csvfile, fieldnames=fieldnames)
            
            if write_header and not file_exists:
                writer.writeheader()
            
            writer.writerow(issue_data)
            csvfile.flush()  # Ensure data is written immediately
            
    except Exception as e:
        print_error(f"Failed to write to CSV: {e}")

def save_enhanced_csv(data: List[Dict], output_path: str):
    """Save the enhanced data with issue descriptions to a new CSV file"""
    try:
        print_info(f"Saving enhanced data to: {output_path}")
        df = pd.DataFrame(data)
        df.to_csv(output_path, index=False)
        print_success(f"Enhanced CSV saved successfully with {len(data)} records")
    except Exception as e:
        print_error(f"Failed to save CSV file: {e}")
        sys.exit(1)


def main():
    """Main function"""
    parser = argparse.ArgumentParser(
        description="Generate GitHub issues for Deshio ERP API endpoints"
    )
    parser.add_argument(
        "--model", 
        required=True,
        help="OpenRouter model ID (e.g., anthropic/claude-3.5-sonnet)"
    )
    parser.add_argument(
        "--input",
        default="doc.csv",
        help="Input CSV file path (default: doc.csv)"
    )
    parser.add_argument(
        "--output",
        default="enhanced_doc.csv",
        help="Output CSV file path (default: enhanced_doc.csv)"
    )
    parser.add_argument(
        "--resume",
        action="store_true",
        help="Resume processing by skipping already completed entries"
    )
    
    args = parser.parse_args()
    
    # Print startup header
    print_header("🚀 Deshio ERP GitHub Issue Generator")
    start_time = datetime.now()
    print_info(f"Started at: {start_time.strftime('%Y-%m-%d %H:%M:%S')}")
    print_info(f"Model: {Fore.YELLOW}{args.model}{Style.RESET_ALL}")
    print_info(f"Input file: {Fore.YELLOW}{args.input}{Style.RESET_ALL}")
    print_info(f"Output file: {Fore.YELLOW}{args.output}{Style.RESET_ALL}")
    print_info(f"Resume mode: {Fore.YELLOW}{'Enabled' if args.resume else 'Disabled'}{Style.RESET_ALL}")
    print_info(f"Continuous writing: {Fore.GREEN}Enabled{Style.RESET_ALL}")
    
    # Check for API key
    api_key = os.getenv("OPENROUTER_API_KEY")
    if not api_key:
        print_error("OPENROUTER_API_KEY environment variable not found")
        print_warning("Please set it in your .env file or environment")
        sys.exit(1)
    else:
        print_success("OpenRouter API key found")
    
    # Initialize OpenRouter client
    print_info("Initializing OpenRouter client...")
    client = OpenRouterClient(api_key)
    print_success("OpenRouter client initialized")
    
    # Load CSV data
    api_data = load_csv_data(args.input)
    
    # Get already processed entries if resume mode is enabled
    processed_entries = set()
    if args.resume:
        processed_entries = get_processed_entries(args.output)
    
    # Process each API endpoint
    print_header("🔄 Processing API Endpoints")
    success_count = 0
    failed_count = 0
    skipped_count = 0
    total_processed = 0
    
    # Initialize CSV file with headers if not resuming or file doesn't exist
    if not args.resume or not os.path.exists(args.output):
        print_info("Initializing output CSV file...")
        header_data = {
            'id': '', 'category': '', 'api_title': '', 'api_description': '', 
            'route': '', 'Type': '', 'Authentication_Type': '', 
            'issue_title': '', 'issue_description': ''
        }
        append_to_csv(header_data, args.output, write_header=True)
        # Remove the sample entry
        with open(args.output, 'r+', encoding='utf-8') as f:
            lines = f.readlines()
            f.seek(0)
            f.writelines(lines[:1])  # Keep only header
            f.truncate()
    
    for i, api in enumerate(api_data, 1):
        # Use 1-based index as ID
        api_id = i
        
        # Skip if already processed
        if api_id in processed_entries:
            skipped_count += 1
            print_progress(i, len(api_data), f"⏭️ SKIPPED: {api['category']} - {api['api_title'][:35]}...")
            continue
        
        # Show progress
        print_progress(i, len(api_data), f"🔄 {api['category']} - {api['api_title'][:35]}...")
        
        # Generate issue description
        issue_result = client.generate_issue_description(args.model, api)
        
        # Prepare complete data for CSV (all original API data + issue data)
        issue_data = {
            'id': api_id,
            'category': api.get('category', ''),
            'api_title': api.get('api_title', ''),
            'api_description': api.get('api_description', ''),
            'route': api.get('route', ''),
            'Type': api.get('Type', ''),
            'Authentication_Type': api.get('Authentication_Type', ''),
            'issue_title': '',
            'issue_description': ''
        }
        
        if issue_result:
            issue_data['issue_title'] = issue_result['title']
            issue_data['issue_description'] = issue_result['description']
            success_count += 1
            print_progress(i, len(api_data), f"✅ DONE: {api['category']} - {api['api_title'][:35]}...")
        else:
            issue_data['issue_title'] = f"Implement {api['api_title']} API"
            issue_data['issue_description'] = "Failed to generate issue description"
            failed_count += 1
            print_progress(i, len(api_data), f"❌ FAILED: {api['category']} - {api['api_title'][:35]}...")
        
        # Write to CSV immediately
        append_to_csv(issue_data, args.output)
        total_processed += 1
        
        # NO DELAY - removed time.sleep(0.1)
    
    print("\n")
    print_success(f"Continuous CSV writing completed to: {args.output}")
    
    # Calculate and display summary
    end_time = datetime.now()
    duration = end_time - start_time
    
    print_header("📊 Summary Report")
    print_success(f"Total API endpoints in input: {len(api_data)}")
    print_success(f"Successfully generated: {success_count}")
    if failed_count > 0:
        print_warning(f"Failed to generate: {failed_count}")
    if skipped_count > 0:
        print_info(f"Skipped (already done): {skipped_count}")
    print_info(f"Actually processed: {total_processed}")
    print_info(f"Processing time: {duration.total_seconds():.1f} seconds")
    if total_processed > 0:
        print_info(f"Average time per endpoint: {(duration.total_seconds() / total_processed):.1f} seconds")
    print_info(f"Completed at: {end_time.strftime('%Y-%m-%d %H:%M:%S')}")
    
    if total_processed == 0:
        print_info("🎯 All endpoints were already processed!")
    elif success_count == total_processed:
        print_success("🎉 All processed endpoints completed successfully!")
    else:
        print_warning(f"⚠️ {failed_count} endpoints had issues - check the logs above")
    
    print(f"\n{Fore.CYAN}Happy coding! 🚀{Style.RESET_ALL}")


if __name__ == "__main__":
    main()


