#!/usr/bin/env python3
"""
GitHub Issue Creator for Deshio ERP API Documentation

This script reads the enhanced CSV file with generated GitHub issue titles and descriptions
and automatically creates issues in the specified GitHub repository.

Usage:
    python github_issues.py --csv enhanced_doc.csv --repo sakhadib/deshio

Features:
- Creates GitHub issues with enhanced descriptions including route information
- Adds proper labels based on API category and authentication type
- Handles resume functionality to skip already created issues
- Provides colored console output with progress tracking
- Includes rate limiting to respect GitHub API limits
"""

import argparse
import json
import os
import sys
import time
from typing import Dict, List, Optional, Set
import pandas as pd
import requests
from dotenv import load_dotenv
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


class GitHubIssueCreator:
    """Client for creating GitHub issues via GitHub API"""
    
    def __init__(self, token: str, repo: str):
        self.token = token
        self.repo = repo
        self.base_url = "https://api.github.com"
        self.headers = {
            "Authorization": f"Bearer {token}",
            "Accept": "application/vnd.github.v3+json",
            "Content-Type": "application/json",
            "User-Agent": "Deshio-ERP-Issue-Creator/1.0"
        }
        self.rate_limit_remaining = 5000
        self.rate_limit_reset = None
    
    def check_rate_limit(self):
        """Check current rate limit status"""
        try:
            response = requests.get(
                f"{self.base_url}/rate_limit",
                headers=self.headers,
                timeout=10
            )
            if response.status_code == 200:
                data = response.json()
                self.rate_limit_remaining = data['resources']['core']['remaining']
                self.rate_limit_reset = data['resources']['core']['reset']
                print_info(f"Rate limit: {self.rate_limit_remaining} requests remaining")
                return True
        except Exception as e:
            print_warning(f"Could not check rate limit: {e}")
        return False
    
    def wait_for_rate_limit(self):
        """Wait if rate limit is low"""
        if self.rate_limit_remaining < 10:
            if self.rate_limit_reset:
                wait_time = max(60, self.rate_limit_reset - int(time.time()) + 10)
                print_warning(f"Rate limit low, waiting {wait_time} seconds...")
                time.sleep(wait_time)
                self.check_rate_limit()
    
    def enhance_description_with_route(self, description: str, route: str, method: str, auth_type: str) -> str:
        """Enhance description by adding route information prominently"""
        
        # Simply add route info at the beginning after overview
        lines = description.split('\\n')
        enhanced_lines = []
        
        for i, line in enumerate(lines):
            enhanced_lines.append(line)
            
            # Add route info right after the overview section and before API Specifications
            if line.strip() == '## Overview' and i + 1 < len(lines):
                # Find the end of overview section
                overview_end = i + 1
                while overview_end < len(lines) and not lines[overview_end].strip().startswith('##'):
                    overview_end += 1
                
                # Insert route information before the next section
                enhanced_lines.extend([
                    '',
                    '## 🔗 **API Endpoint Details**',
                    '',
                    '```',
                    f'{method.upper()} /{route}',
                    f'Authentication: {auth_type}',
                    '```',
                    ''
                ])
                break
        
        return '\\n'.join(enhanced_lines)
    
    def generate_labels(self, category: str, method: str, auth_type: str) -> List[str]:
        """Generate appropriate labels for the issue"""
        labels = []
        
        # Category-based labels
        labels.append(f"api:{category}")
        
        # Method-based labels
        method_colors = {
            'get': 'enhancement',
            'post': 'feature',
            'put': 'enhancement', 
            'patch': 'enhancement',
            'delete': 'enhancement'
        }
        if method.lower() in method_colors:
            labels.append(method_colors[method.lower()])
        
        # Authentication-based labels
        if str(auth_type).lower() in ['none', 'nan']:
            labels.append('public-api')
        elif str(auth_type).lower() == 'admin':
            labels.append('admin-only')
        elif str(auth_type).lower() == 'employee':
            labels.append('authenticated')
        
        # General labels
        labels.extend(['api-implementation', 'backend'])
        
        return list(set(labels))  # Remove duplicates
    
    def create_issue(self, title: str, description: str, labels: List[str]) -> Optional[Dict]:
        """Create a GitHub issue"""
        
        self.wait_for_rate_limit()
        
        payload = {
            "title": title,
            "body": description,
            "labels": labels
        }
        
        try:
            response = requests.post(
                f"{self.base_url}/repos/{self.repo}/issues",
                headers=self.headers,
                json=payload,
                timeout=30
            )
            
            # Update rate limit info from headers
            if 'X-RateLimit-Remaining' in response.headers:
                self.rate_limit_remaining = int(response.headers['X-RateLimit-Remaining'])
            
            response.raise_for_status()
            
            issue_data = response.json()
            return {
                "number": issue_data["number"],
                "url": issue_data["html_url"],
                "api_url": issue_data["url"]
            }
            
        except requests.exceptions.RequestException as e:
            print_error(f"Failed to create issue: {e}")
            if hasattr(e, 'response') and e.response is not None:
                print_error(f"Response: {e.response.text}")
            return None
    
    def get_existing_issues(self) -> Set[str]:
        """Get existing issue titles to avoid duplicates"""
        existing_titles = set()
        page = 1
        
        print_info("Fetching existing issues to avoid duplicates...")
        
        while True:
            try:
                response = requests.get(
                    f"{self.base_url}/repos/{self.repo}/issues",
                    headers=self.headers,
                    params={"state": "all", "per_page": 100, "page": page},
                    timeout=30
                )
                response.raise_for_status()
                
                issues = response.json()
                if not issues:
                    break
                
                for issue in issues:
                    existing_titles.add(issue["title"])
                
                page += 1
                
                # Rate limiting
                time.sleep(0.1)
                
            except Exception as e:
                print_warning(f"Could not fetch existing issues: {e}")
                break
        
        print_info(f"Found {len(existing_titles)} existing issues")
        return existing_titles


def load_csv_data(file_path: str) -> List[Dict]:
    """Load enhanced CSV data"""
    try:
        print_info(f"Loading CSV data from: {file_path}")
        df = pd.read_csv(file_path)
        # Filter out rows with empty issue_title or issue_description
        df = df.dropna(subset=['issue_title', 'issue_description'])
        df = df[df['issue_title'].str.strip() != '']
        df = df[df['issue_description'].str.strip() != '']
        print_success(f"Successfully loaded {len(df)} valid records")
        return df.to_dict('records')
    except FileNotFoundError:
        print_error(f"CSV file '{file_path}' not found")
        sys.exit(1)
    except Exception as e:
        print_error(f"Failed to read CSV file: {e}")
        sys.exit(1)


def save_results_log(results: List[Dict], output_path: str):
    """Save results to a log file"""
    try:
        with open(output_path, 'w', encoding='utf-8') as f:
            json.dump(results, f, indent=2, ensure_ascii=False)
        print_success(f"Results saved to: {output_path}")
    except Exception as e:
        print_error(f"Failed to save results: {e}")


def main():
    """Main function"""
    parser = argparse.ArgumentParser(
        description="Create GitHub issues from Deshio ERP API documentation CSV"
    )
    parser.add_argument(
        "--csv",
        default="enhanced_doc.csv",
        help="Input enhanced CSV file path (default: enhanced_doc.csv)"
    )
    parser.add_argument(
        "--repo",
        default="sakhadib/deshio",
        help="GitHub repository (default: sakhadib/deshio)"
    )
    parser.add_argument(
        "--output",
        default="github_issues_log.json",
        help="Output log file for created issues (default: github_issues_log.json)"
    )
    parser.add_argument(
        "--skip-existing",
        action="store_true",
        help="Skip issues that already exist (check by title)"
    )
    parser.add_argument(
        "--dry-run",
        action="store_true",
        help="Dry run mode - don't actually create issues"
    )
    parser.add_argument(
        "--limit",
        type=int,
        help="Limit number of issues to create (for testing)"
    )
    
    args = parser.parse_args()
    
    # Print startup header
    print_header("🚀 Deshio ERP GitHub Issue Creator")
    start_time = datetime.now()
    print_info(f"Started at: {start_time.strftime('%Y-%m-%d %H:%M:%S')}")
    print_info(f"Repository: {Fore.YELLOW}{args.repo}{Style.RESET_ALL}")
    print_info(f"CSV file: {Fore.YELLOW}{args.csv}{Style.RESET_ALL}")
    print_info(f"Output log: {Fore.YELLOW}{args.output}{Style.RESET_ALL}")
    print_info(f"Skip existing: {Fore.YELLOW}{'Yes' if args.skip_existing else 'No'}{Style.RESET_ALL}")
    print_info(f"Dry run: {Fore.YELLOW}{'Yes' if args.dry_run else 'No'}{Style.RESET_ALL}")
    if args.limit:
        print_info(f"Limit: {Fore.YELLOW}{args.limit}{Style.RESET_ALL}")
    
    # Check for GitHub token
    github_token = os.getenv("GITHUB_TOKEN")
    if not github_token:
        print_error("GITHUB_TOKEN environment variable not found")
        print_warning("Please set it in your .env file or environment")
        sys.exit(1)
    else:
        print_success("GitHub token found")
    
    # Initialize GitHub client
    print_info("Initializing GitHub API client...")
    client = GitHubIssueCreator(github_token, args.repo)
    
    # Check rate limit
    if not client.check_rate_limit():
        print_warning("Could not verify rate limit, proceeding anyway...")
    
    # Load CSV data
    csv_data = load_csv_data(args.csv)
    
    # Apply limit if specified
    if args.limit:
        csv_data = csv_data[:args.limit]
        print_info(f"Limited to {len(csv_data)} issues")
    
    # Get existing issues if needed
    existing_issues = set()
    if args.skip_existing:
        existing_issues = client.get_existing_issues()
    
    # Process each CSV row
    print_header("🔄 Creating GitHub Issues")
    results = []
    success_count = 0
    failed_count = 0
    skipped_count = 0
    
    for i, row in enumerate(csv_data, 1):
        # Extract data
        issue_title = str(row.get('issue_title', '')).strip()
        issue_description = str(row.get('issue_description', '')).strip()
        route = str(row.get('route', '')).strip()
        method = str(row.get('Type', '')).strip()
        category = str(row.get('category', '')).strip()
        auth_type = str(row.get('Authentication_Type', '')).strip()
        
        # Skip if already exists
        if args.skip_existing and issue_title in existing_issues:
            skipped_count += 1
            print_progress(i, len(csv_data), f"⏭️ SKIPPED: {issue_title[:50]}...")
            results.append({
                "id": row.get('id'),
                "title": issue_title,
                "status": "skipped",
                "reason": "already exists"
            })
            continue
        
        # Show progress
        print_progress(i, len(csv_data), f"🔄 {category} - {issue_title[:40]}...")
        
        if args.dry_run:
            print_progress(i, len(csv_data), f"🧪 DRY RUN: {issue_title[:40]}...")
            results.append({
                "id": row.get('id'),
                "title": issue_title,
                "status": "dry_run",
                "would_create": True
            })
            success_count += 1
            continue
        
        # Enhance description with route information
        enhanced_description = client.enhance_description_with_route(
            issue_description, route, method, auth_type
        )
        
        # Generate labels
        labels = client.generate_labels(category, method, auth_type)
        
        # Create issue
        issue_result = client.create_issue(issue_title, enhanced_description, labels)
        
        if issue_result:
            success_count += 1
            print_progress(i, len(csv_data), f"✅ CREATED: #{issue_result['number']} - {issue_title[:30]}...")
            results.append({
                "id": row.get('id'),
                "title": issue_title,
                "status": "created",
                "issue_number": issue_result["number"],
                "issue_url": issue_result["url"],
                "labels": labels,
                "route": route,
                "method": method,
                "category": category
            })
        else:
            failed_count += 1
            print_progress(i, len(csv_data), f"❌ FAILED: {issue_title[:40]}...")
            results.append({
                "id": row.get('id'),
                "title": issue_title,
                "status": "failed",
                "route": route,
                "method": method,
                "category": category
            })
        
        # Small delay to be respectful to GitHub API
        time.sleep(0.2)
    
    print("\n")
    
    # Save results
    save_results_log(results, args.output)
    
    # Calculate and display summary
    end_time = datetime.now()
    duration = end_time - start_time
    
    print_header("📊 Summary Report")
    print_success(f"Total issues processed: {len(csv_data)}")
    print_success(f"Successfully created: {success_count}")
    if failed_count > 0:
        print_warning(f"Failed to create: {failed_count}")
    if skipped_count > 0:
        print_info(f"Skipped (already exist): {skipped_count}")
    print_info(f"Processing time: {duration.total_seconds():.1f} seconds")
    print_info(f"Average time per issue: {(duration.total_seconds() / len(csv_data)):.1f} seconds")
    print_info(f"Completed at: {end_time.strftime('%Y-%m-%d %H:%M:%S')}")
    
    if not args.dry_run:
        if success_count == len(csv_data) - skipped_count:
            print_success("🎉 All issues created successfully!")
        else:
            print_warning(f"⚠️ {failed_count} issues failed - check the logs")
        
        print_info(f"🔗 View issues at: https://github.com/{args.repo}/issues")
    else:
        print_info("🧪 Dry run completed - no issues were actually created")
    
    print(f"\n{Fore.CYAN}Happy coding! 🚀{Style.RESET_ALL}")


if __name__ == "__main__":
    main()