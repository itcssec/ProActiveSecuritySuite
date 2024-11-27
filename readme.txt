=== Wordfence2Cloudflare ===
Contributors: ITCS, freemius
Tags: Wordfence, Cloudflare, Security, Wordpress Security, Firewall
Requires at least: 5.2
Requires PHP: 7.4
Tested up to: 6.7
Stable tag: 1.5.1
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

This plugin takes blocked IPs from Wordfence and adds them to the Cloudflare firewall blocked list.

== Description ==

This plugin enhances the security of your WordPress website by seamlessly integrating Wordfence and Cloudflare. It automatically synchronizes blocked IPs from Wordfence and adds them to the Cloudflare firewall blocked list, providing an additional layer of protection against malicious traffic.

Features:

Automatic IP Synchronization: The plugin periodically checks for new blocked IPs in Wordfence and automatically adds them to Cloudflare's blocked IP list. This ensures that known malicious IPs are effectively blocked at the Cloudflare level, reducing the burden on your server.

Customizable Settings: The plugin allows you to configure various settings, including Cloudflare credentials (email and zone ID), blocked hits threshold, block scope (domain specific or entire account), and cron interval. These settings can be adjusted according to your specific security requirements.

Manual Process Trigger: You can manually trigger the synchronization process with a single click, giving you control over when the synchronization occurs. This can be helpful in urgent situations or when you want to ensure immediate synchronization of blocked IPs.

The Wordfence to Cloudflare plugin now includes an optional integration with AbuseIPDB's API. This enhances the overall functionality and gives users the ability to obtain additional information about the blocked IPs. With this new feature, users can gather comprehensive details about the origin and credibility of the malicious traffic, contributing to a more in-depth understanding of security threats.

AbuseIPDB Integration: The plugin can now optionally connect to AbuseIPDB's API. Once enabled, this feature allows you to fetch and display additional details about the IPs blocked by Wordfence. This additional information includes country code, usage type, ISP, and a confidence score, further enhancing the transparency and control over your website's security.

Country Code: Identify the geographic origin of the blocked IP addresses. This feature can help understand if your site is being targeted from specific regions.

Usage Type: Get to know the type of entity using the blocked IP. This knowledge can provide insights into the nature of the potential threats.

ISP Information: Obtain details about the internet service provider of the blocked IP.

Confidence Score: AbuseIPDB provides a confidence score that indicates how likely the IP is to engage in abusive behavior. This feature can be useful for prioritizing responses to threats.

Pro Features:

Integration with WhatIsMyBrowser.com API
This plugin now features seamless integration with the WhatIsMyBrowser.com API, providing you with enhanced user agent analysis and detection capabilities. The WhatIsMyBrowser.com API allows you to obtain detailed information about user agents, including software, operating systems, and more. This integration empowers you to better understand and manage the traffic hitting your WordPress site.

Benefits:
Advanced User Agent Analysis: With the WhatIsMyBrowser.com API, you can gain deeper insights into the user agents accessing your website. This includes information about the software, operating systems, and more.

Abusive User Detection: The plugin can now detect potentially abusive or malicious user agents based on the API's analysis. It flags user agents that may pose a security risk, helping you proactively secure your website.

Automated Blocking: You can automatically block or manage potentially abusive user agents, safeguarding your website from potential threats. The user can send the ips that wishes to block to Cloudflare by clicking the button on each record.  

How to Use:
Obtain an API Key: To utilize this feature, you need an API key from WhatIsMyBrowser.com. You can obtain an API key by signing up for a free or premium account on their platform.

API Key Integration: Once you have your API key, enter it into the plugin settings. This will enable the plugin to communicate with the WhatIsMyBrowser.com API.

Enhanced User Agent Analysis: Upon activation, the plugin will start analyzing user agents hitting your site. The information gathered through the API integration will be displayed in the captured traffic data section of the plugin.

Abusive User Detection: The plugin will automatically mark user agents that are identified as potentially abusive by the API. This enables you to take appropriate actions to protect your site.

Please note that an API key is required for this feature to function effectively. Whether you choose a free or premium account on WhatIsMyBrowser.com, this integration will help you elevate your website's security and user agent analysis capabilities.

To get started, simply sign up for an account on WhatIsMyBrowser.com, obtain your API key, and integrate it into the plugin settings.

Security Benefits:

Enhanced IP Blocking: By combining the powerful IP blocking capabilities of Wordfence and Cloudflare, this plugin strengthens your website's defense against malicious traffic. It ensures that blocked IPs identified by Wordfence are effectively blocked at the Cloudflare level, preventing them from reaching your server.

Secure Cloudflare Key Storage: The plugin securely stores your Cloudflare key using WordPress's built-in options table. The key is encrypted and can only be accessed by authorized processes, providing an additional layer of protection for your key.

Reduced Server Load: Offloading the blocking of malicious IPs to Cloudflare reduces the load on your server, improving its performance and responsiveness. This is particularly beneficial during DDoS attacks or when dealing with a large number of blocked IPs.

Customizable Security Settings: The plugin offers flexible settings that allow you to tailor the security measures to your specific needs. You can adjust the blocked hits threshold and choose between domain-specific or account-wide blocking, providing granular control over the IP blocking process.

Seamless Integration: The plugin seamlessly integrates with your existing Wordfence and Cloudflare configurations. It leverages the APIs provided by both services, ensuring smooth and reliable synchronization of blocked IPs without any manual intervention.

By utilizing the combined power of Wordfence and Cloudflare, this plugin helps safeguard your WordPress website from malicious IPs more effectively. It automates the synchronization process, reduces server load, and provides customizable security settings, all while ensuring the secure storage of your Cloudflare key. With Wordfence to Cloudflare, you can enhance the security posture of your website and protect it from a wide range of security threats.


== Installation: ==

Upload the wordfence-to-cloudflare directory to your /wp-content/plugins/ directory, or install the plugin through the WordPress plugins screen directly.
Activate the plugin through the 'Plugins' screen in WordPress.
Use the Settings -> Wordfence to Cloudflare screen to configure the plugin.

== Frequently Asked Questions: ==

Can I manually trigger the IP synchronization process?
Yes, the plugin provides a button to manually trigger the synchronization process. This can be helpful in urgent situations or when you want to ensure immediate synchronization of blocked IPs.

Where is my Cloudflare key stored?
Your Cloudflare key is securely stored in the WordPress's built-in options table. It is encrypted and can only be accessed by authorized processes.

Can I customize the security settings of the plugin?
Yes, the plugin offers flexible settings that allow you to adjust the blocked hits threshold, choose between domain-specific or account-wide blocking, set the cron interval, and enter your Cloudflare credentials.

How does the plugin reduce the server load?
By offloading the task of blocking malicious IPs to Cloudflare, the plugin reduces the load on your server. This can improve its performance and responsiveness, particularly during DDoS attacks or when dealing with a large number of blocked IPs.

What are the requirements to use this plugin?
This plugin requires an active Wordfence and Cloudflare account. You also need to have access to your Cloudflare API key and Zone ID to configure the plugin.

== Screenshots ==

1. The plugin main settings page

== Upgrade Notice ==

= 1.5.1 =

Bulk Selection and Deletion in Blocked IPs Tab:

Added a "Select All" checkbox in the Blocked IPs table header, allowing you to select all records across all pages, regardless of how many are displayed per page.
Implemented the ability to perform bulk actions, such as deleting multiple blocked IPs at once, enhancing efficiency in managing large numbers of records.
Improved the positioning and styling of the "Select All" checkbox to prevent overlap with sorting arrows, ensuring better usability.
Enhanced Actions in Captured Traffic Tab:

Introduced a new "Delete All Records" button within the "Actions" column of the Captured Traffic table. This feature enables you to delete all records associated with a specific IP address with a single click.
Adjusted the layout and styling of action buttons to prevent them from overflowing outside of their table columns. Buttons are now stacked vertically and styled consistently for a cleaner and more organized look.
User Interface Improvements:

Modified CSS and table configurations to ensure that all buttons and content fit neatly within their respective columns, enhancing the overall aesthetics and usability of the plugin interface.
Disabled sorting on checkbox columns and adjusted column widths to optimize the display of data and controls within the tables.



== Changelog ==

= 1.2 =
Added new functionalities to work with cloudflare including a table and an extra tab

= 1.2.1 =
New options on the blocked ips table

= 1.3 =
The user now can remove a blocked ip either from the local list or from both the Cloudflare and the plugin blocked list. 

= 1.3.1 =
Freemius Integration

= 1.3.2 =
Minor fixes

= 1.3.3 =
AbuseIP Database Integration

= 1.3.4 =
Timezone fix

= 1.3.5 =
Timezone fix

= 1.3.6 =
Added ips blocked from WordFence wfBlockedIPLog too

= 1.3.7 =
Various fixes
Added Pro feature - Traffic inspection integration with whatsmybrowser.com API to identify malicious user agents. 

= 1.3.8 =
Various fixes

= 1.3.9 =
Various fixes

= 1.4.0 =
Various fixes

= 1.5.0 =
Enhanced WhatIsMyBrowser API Integration:
- Dynamic handling of `is_abusive` values directly from the API without predefined mappings.
- Extracted and stored specific API response elements: `is_abusive`, `software`, `operating_system`.
- Optimized API usage by making calls only once per unique IP/User Agent combination.
- Improved error handling and logging for API requests.

Admin Interface Enhancements:
- Professional redesign of the "Captured Traffic" table with WordPress admin styling.
- Fixed layout issues with action buttons and ensured proper alignment.
- Integrated DataTables for advanced table features like search, pagination, and sorting.
- Focused data display by showing only relevant API response fields.

Database and Performance Optimizations:
- Updated database schema to include new columns for `software` and `operating_system`.
- Changed `is_abusive` column type to accommodate dynamic values.
- Enhanced data sanitization and security measures.

Code Refactoring and Security Enhancements:
- Improved code structure for better readability and maintenance.
- Ensured adherence to security best practices throughout the codebase.

Bug Fixes and Minor Improvements:
- Resolved button overflow issues in the admin table.
- Enhanced user experience with better messaging and error handling.

= 1.5.1 =

Bulk Selection and Deletion in Blocked IPs Tab:

Added a "Select All" checkbox in the Blocked IPs table header, allowing you to select all records across all pages, regardless of how many are displayed per page.
Implemented the ability to perform bulk actions, such as deleting multiple blocked IPs at once, enhancing efficiency in managing large numbers of records.
Improved the positioning and styling of the "Select All" checkbox to prevent overlap with sorting arrows, ensuring better usability.
Enhanced Actions in Captured Traffic Tab:

Introduced a new "Delete All Records" button within the "Actions" column of the Captured Traffic table. This feature enables you to delete all records associated with a specific IP address with a single click.
Adjusted the layout and styling of action buttons to prevent them from overflowing outside of their table columns. Buttons are now stacked vertically and styled consistently for a cleaner and more organized look.
User Interface Improvements:

Modified CSS and table configurations to ensure that all buttons and content fit neatly within their respective columns, enhancing the overall aesthetics and usability of the plugin interface.
Disabled sorting on checkbox columns and adjusted column widths to optimize the display of data and controls within the tables.
