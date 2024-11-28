# Wordfence to Cloudflare
Enhance your WordPress website's security by seamlessly integrating Wordfence with Cloudflare. The Wordfence to Cloudflare plugin automatically synchronizes blocked IPs from Wordfence to Cloudflare's firewall, providing an additional layer of protection against malicious traffic.

Table of Contents

• Free Features

• Premium Features

• Benefits

• Installation

• Usage

• Frequently Asked Questions

# Features
Free Features:

Automatic IP Synchronization

Periodically checks for new blocked IPs in Wordfence and automatically adds them to Cloudflare's blocked IP list. This ensures that known malicious IPs are effectively blocked at the Cloudflare level, reducing the burden on your server.

# Customizable Settings

Cloudflare Credentials: Securely store and manage your Cloudflare Email, API Key, Zone ID, and Account ID.

Blocked Hits Threshold: Set a threshold for blocked hits to determine which IPs should be synchronized.

Block Scope: Choose between domain-specific or account-wide blocking on Cloudflare.
Block Mode: Select the action to be taken on Cloudflare (e.g., Block, Managed Challenge).

Cron Interval: Configure how frequently the plugin synchronizes blocked IPs (e.g., every 5 minutes, hourly).

Manual Synchronization

Manually trigger the synchronization process with a single click, ensuring immediate synchronization of blocked IPs when needed.

AbuseIPDB Integration (Optional)

Enhanced IP Data: Fetch additional details about blocked IPs from AbuseIPDB, including Country Code, Usage Type, ISP, and Confidence Score.

Country Code: Identify the geographic origin of blocked IPs.

Usage Type: Understand the nature of the entity using the IP (e.g., ISP, Commercial, Residential).

ISP Information: Obtain details about the Internet Service Provider of the blocked IP.

Confidence Score: Gauge how likely an IP is to be engaged in abusive behavior.

Premium Features:

Captured Traffic Data

Traffic Logging: Capture and log traffic data, including IP addresses, user agents, request methods, and accessed URLs.

User Agent Analysis: Analyze user agents to detect potentially abusive or malicious clients.

Exclude User Roles: Option to exclude specific WordPress user roles from traffic logging.

WhatIsMyBrowser.com API Integration

Advanced User Agent Analysis: Obtain detailed information about user agents accessing your website, including software, operating systems, and more.

Abusive User Detection: Automatically detect and flag potentially abusive or malicious user agents based on API analysis.

Automated Blocking: Option to send IPs associated with abusive user agents to Cloudflare directly from the Captured Traffic interface.

Enhanced AbuseIPDB Integration

Automatic Data Update: When AbuseIPDB data is fetched for an IP address, all existing entries with that IP in the Captured Traffic table are updated to reflect the latest data.

Priority Support

Access to priority support for assistance with plugin setup and troubleshooting.

# Benefits

Enhanced Security

Combines the powerful IP blocking capabilities of Wordfence and Cloudflare to provide robust protection against malicious traffic.

Reduced Server Load

Offloads IP blocking to Cloudflare, reducing the burden on your server and improving performance.

Comprehensive Traffic Analysis

Gain deeper insights into the traffic hitting your website, identify potential threats, and take proactive measures.

Customizable Protection

Tailor security measures to your specific needs with flexible settings and options.

Secure Credential Storage

Cloudflare API keys and other sensitive data are securely stored using WordPress's built-in options API.

# Installation

Upload Plugin

Upload the wordfence-to-cloudflare directory to the /wp-content/plugins/ directory.

Alternatively, install the plugin through the WordPress Plugins screen directly.

Activate Plugin

Activate the plugin through the 'Plugins' screen in WordPress.

Configure Settings

Navigate to Settings > WTC Settings in your WordPress dashboard.

Enter your Cloudflare credentials and configure the desired settings.

Usage

Cloudflare Credentials

Email: Your Cloudflare account email address.

API Key: Your Cloudflare API key (Global API Key or API Token with appropriate permissions).

Zone ID: The Zone ID of the domain you wish to protect.

Account ID: Your Cloudflare Account ID.

Configure Plugin Settings

Blocked Hits Threshold: Set the minimum number of blocked hits before an IP is synchronized to Cloudflare.

Block Scope: Choose 'Domain Specific' or 'Entire Account' for the scope of the block.

Block Mode: Select the action to be taken (e.g., 'Block', 'Managed Challenge').

Cron Interval: Set how frequently the synchronization should occur.

AbuseIPDB Integration (Optional)

API Key: Obtain an API key from AbuseIPDB and enter it in the plugin settings.

Enable Lookup: Check the option to enable AbuseIPDB lookups.

WhatIsMyBrowser.com Integration (Premium)

API Key: Obtain an API key from WhatIsMyBrowser.com and enter it in the plugin settings.

Enable Integration: Ensure the feature is enabled in the settings.

Captured Traffic Data (Premium)

Enable Traffic Capture: Enable traffic logging in the plugin settings.

Exclude Roles: Select user roles to exclude from traffic logging.

View Captured Data: Navigate to the 'Captured Traffic Data' tab to view and manage logged traffic.

Manual Synchronization

Click the 'Run Process' button in the plugin settings to manually trigger synchronization.

Frequently Asked Questions
Q1: Can I manually trigger the IP synchronization process?

A: Yes, the plugin provides a 'Run Process' button in the settings. This allows you to manually trigger the synchronization process at any time.

Q2: How is my Cloudflare API key stored?

A: Your Cloudflare API key is securely stored in the WordPress options table using appropriate security measures. It is not displayed in plain text once saved.

Q3: Can I customize which IPs are synchronized to Cloudflare?

A: Yes, you can set a 'Blocked Hits Threshold' to determine which IPs should be synchronized based on the number of blocked hits.

Q4: What are the requirements to use this plugin?

A: You need an active account with Cloudflare and to have the Wordfence plugin installed and activated on your site. For optional features, you may need API keys from AbuseIPDB and WhatIsMyBrowser.com.

Q5: How does the plugin reduce server load?

A: By blocking malicious IPs at the Cloudflare level, the plugin prevents unwanted traffic from reaching your server, thereby reducing load and improving performance.

Q6: Is the Captured Traffic Data feature available in the free version?

A: No, the Captured Traffic Data feature is available in the premium version of the plugin.

Q7: How do I obtain API keys for AbuseIPDB and WhatIsMyBrowser.com?

A: Visit AbuseIPDB and WhatIsMyBrowser.com to sign up for accounts and obtain API keys.

Q8: Can I exclude certain users from being logged in Captured Traffic Data?

A: Yes, in the premium version, you can exclude specific WordPress user roles from being logged.

Q9: Does the plugin support IPv6 addresses?

A: Yes, the plugin supports both IPv4 and IPv6 addresses.
