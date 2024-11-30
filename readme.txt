=== Wordfence2Cloudflare ===
Contributors: ITCS, freemius
Tags: Wordfence, Cloudflare, Security, Wordpress Security, Firewall
Requires at least: 5.2
Requires PHP: 7.4
Tested up to: 6.7
Stable tag: 1.5.4
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

<h1>Welcome to the <em>ProActive Security Suite</em> Plugin Wiki</h1>

<p>
    Enhance your WordPress website's security with the <strong>ProActive Security Suite</strong>. This powerful plugin offers advanced security features including automatic IP blocking, an advanced rule builder, traffic analysis, and seamless integration with services like <strong>Cloudflare</strong>, <strong>AbuseIPDB</strong>,  and <strong>Whatismybrowser.com</strong>. ProActive Security Suite provides proactive defense mechanisms to protect your site from malicious traffic and potential threats before they reach your server.
</p>

<div class="toc">
    <h2>Table of Contents</h2>
    <ul>
        <li><a href="#introduction">Introduction</a></li>
        <li><a href="#features">Features</a></li>
        <ul>
            <li><a href="#free-features">Free Features</a></li>
            <li><a href="#premium-features">Premium Features</a></li>
        </ul>
        <li><a href="#installation">Installation</a></li>
        <li><a href="#configuration">Configuration</a></li>
        <ul>
            <li><a href="#cloudflare-settings">Cloudflare Settings</a></li>
            <li><a href="#abuseipdb-integration">AbuseIPDB Integration</a></li>
            <li><a href="#whatismybrowsercom-integration">WhatIsMyBrowser.com Integration</a></li>
        </ul>
        <li><a href="#usage">Usage</a></li>
        <ul>
            <li><a href="#automatic-ip-synchronization">Automatic IP Synchronization</a></li>
            <li><a href="#manual-synchronization">Manual Synchronization</a></li>
            <li><a href="#captured-traffic-data">Captured Traffic Data</a></li>
            <li><a href="#advanced-rule-builder">Advanced Rule Builder</a></li>
            <li><a href="#rule-details-in-blocked-ips">Rule Details in Blocked IPs</a></li>
        </ul>
        <li><a href="#advanced-settings">Advanced Settings</a></li>
        <li><a href="#faq">Frequently Asked Questions</a></li>
        <li><a href="#support">Support and Contribution</a></li>
        <li><a href="#license">License</a></li>
    </ul>
</div>

<h2 id="introduction">Introduction</h2>

<p>
    Welcome to the <strong>ProActive Security Suite</strong> plugin! This comprehensive security solution enhances your website's protection by combining advanced threat detection, automated rule-based actions, and integration with services like <strong>Cloudflare</strong> and <strong>AbuseIPDB</strong>. By proactively analyzing traffic and applying custom security rules, ProActive Security Suite stops malicious traffic before it reaches your server, reducing load and enhancing performance.
</p>

<a href="https://github.com/itcssec/ProActiveSecuritySuite/releases" class="button" target="_blank">Download Latest Release</a>

<h2 id="features">Features</h2>

<h3 id="free-features">Free Features</h3>

<ul>
    <li><strong>Automatic IP Synchronization:</strong> Effortlessly sync blocked IPs to Cloudflare's firewall.</li>
    <li><strong>Customizable Settings:</strong> Tailor the plugin to your needs with adjustable settings:
        <ul>
            <li>Blocked Hits Threshold</li>
            <li>Block Scope (Domain or Account)</li>
            <li>Block Mode (e.g., Block, Managed Challenge)</li>
            <li>Cron Interval</li>
        </ul>
    </li>
    <li><strong>Manual Synchronization:</strong> Trigger synchronization manually when immediate action is needed.</li>
    <li><strong>AbuseIPDB Integration:</strong> Optional integration to fetch detailed information about IPs:
        <ul>
            <li>Country Code</li>
            <li>Usage Type</li>
            <li>ISP Information</li>
            <li>Confidence Score</li>
        </ul>
    </li>
</ul>

<h3 id="premium-features">Premium Features</h3>

<ul>
    <li><strong>Advanced Rule Builder:</strong> Create custom security rules based on various criteria such as confidence score, whitelisted status, abusive status, and more. Automate actions like blocking or challenging IPs based on these rules.</li>
    <li><strong>Rule Priorities:</strong> Assign priorities to your rules to control the order of evaluation. Higher priority numbers are evaluated first, allowing critical rules to take precedence.</li>
    <li><strong>Automatic Action Application:</strong> The plugin automatically applies actions to IPs that match your defined rules immediately after capturing traffic data.</li>
    <li><strong>Rule Details in Blocked IPs:</strong> View detailed information about which rules caused IPs to be blocked, including criteria and actions taken.</li>
    <li><strong>Captured Traffic Data:</strong> Log and analyze incoming traffic for enhanced security insights.</li>
    <li><strong>Exclude User Roles:</strong> Exclude specific WordPress user roles from traffic logging.</li>
    <li><strong>WhatIsMyBrowser.com API Integration:</strong> Advanced user agent analysis and detection capabilities.</li>
    <li><strong>Enhanced AbuseIPDB Integration:</strong> Automatic updates for all entries with the same IP address.</li>
    <li><strong>Priority Support:</strong> Access dedicated support for assistance and troubleshooting.</li>
</ul>

<div class="note">
    <strong>Note:</strong> The premium features require an active premium license. Upgrade to access these advanced functionalities.
</div>

<h2 id="installation">Installation</h2>

<ol>
    <li><strong>Download the Plugin:</strong> Clone the repository or <a href="https://github.com/itcssec/ProActiveSecuritySuite/releases" target="_blank">download the latest release</a>.</li>
    <li><strong>Upload to WordPress:</strong> Upload the <code>proactive-security-suite</code> directory to <code>/wp-content/plugins/</code>.</li>
    <li><strong>Activate the Plugin:</strong> In your WordPress dashboard, navigate to <em>Plugins</em> and activate <em>ProActive Security Suite</em>.</li>
</ol>

<h2 id="configuration">Configuration</h2>

<h3 id="cloudflare-settings">Cloudflare Settings</h3>

<ol>
    <li><strong>Obtain Cloudflare Credentials:</strong>
        <ul>
            <li><strong>Email:</strong> Your Cloudflare account email.</li>
            <li><strong>API Key:</strong> Your Global API Key or an API Token with necessary permissions.</li>
            <li><strong>Zone ID:</strong> Found in your Cloudflare dashboard under the domain's overview.</li>
            <li><strong>Account ID:</strong> Located in your Cloudflare profile settings.</li>
        </ul>
    </li>
    <li><strong>Configure Plugin Settings:</strong>
        <ul>
            <li>Navigate to <em>Settings &gt; ProActive Security Suite</em>.</li>
            <li>Enter your Cloudflare credentials securely.</li>
            <li>Adjust settings like Blocked Hits Threshold, Block Scope, and Block Mode.</li>
        </ul>
    </li>
</ol>

<h3 id="abuseipdb-integration">AbuseIPDB Integration</h3>

<ol>
    <li><strong>Sign Up for AbuseIPDB:</strong> Visit <a href="https://www.abuseipdb.com/">AbuseIPDB</a> and sign up for an API key.</li>
    <li><strong>Enable Integration:</strong>
        <ul>
            <li>In the plugin settings, enter your AbuseIPDB API key.</li>
            <li>Enable the <em>AbuseIPDB Lookup</em> option.</li>
        </ul>
    </li>
</ol>

<h3 id="whatismybrowsercom-integration">WhatIsMyBrowser.com Integration (Premium)</h3>

<ol>
    <li><strong>Obtain API Key:</strong> Register at <a href="https://developers.whatismybrowser.com/api/">WhatIsMyBrowser.com</a> for an API key.</li>
    <li><strong>Configure Integration:</strong>
        <ul>
            <li>Enter the API key in the plugin's settings under <em>WhatIsMyBrowser API Key</em>.</li>
            <li>Enable the integration features as desired.</li>
        </ul>
    </li>
</ol>

<h2 id="usage">Usage</h2>

<h3 id="automatic-ip-synchronization">Automatic IP Synchronization</h3>

<p>
    The plugin automatically syncs blocked IPs based on your configured cron interval. IPs exceeding the Blocked Hits Threshold are added to Cloudflare's firewall or acted upon based on your defined rules.
</p>

<h3 id="manual-synchronization">Manual Synchronization</h3>

<p>
    Navigate to <em>Settings &gt; ProActive Security Suite</em> and click the <strong>Run Process</strong> button to trigger synchronization and rule evaluation immediately.
</p>

<h3 id="captured-traffic-data">Captured Traffic Data (Premium)</h3>

<p>
    Access detailed logs under the <em>Captured Traffic Data</em> tab. Analyze user agents, request methods, and more. Exclude specific user roles from logging in the settings.
</p>

<h3 id="advanced-rule-builder">Advanced Rule Builder (Premium)</h3>

<p>
    The plugin features a powerful <strong>Rule Builder</strong> that allows you to create custom security rules based on various criteria. You can define rules using conditions such as:
</p>

<ul>
    <li><strong>Confidence Score:</strong> Set thresholds using operators like greater than, less than, equal to, etc.</li>
    <li><strong>Is Whitelisted:</strong> Check if an IP is marked as whitelisted in AbuseIPDB.</li>
    <li><strong>Is Abusive:</strong> Determine if an IP is associated with abusive behavior.</li>
    <li><strong>Custom Criteria:</strong> Add other criteria based on the data captured.</li>
</ul>

<p>
    Each rule can be assigned an <strong>Action</strong> (e.g., Block, Managed Challenge) that will be applied to IPs matching the rule. You can also assign a <strong>Priority</strong> to control the order in which rules are evaluated.
</p>

<h3 id="rule-details-in-blocked-ips">Rule Details in Blocked IPs (Premium)</h3>

<p>
    The <strong>Blocked IPs</strong> tab now includes a <em>Rule Details</em> column that displays comprehensive information about the rules that caused IPs to be blocked. This includes:
</p>

<ul>
    <li><strong>Criteria:</strong> The specific conditions that were met, such as confidence score thresholds, whitelisted status, and more.</li>
    <li><strong>Action:</strong> The action taken by the rule (e.g., Block, Managed Challenge).</li>
</ul>

<p>
    This enhancement allows administrators to easily identify which rules are triggering blocks and understand the reasons behind each IP being blocked. It provides greater transparency and aids in fine-tuning security settings.
</p>

<h2 id="advanced-settings">Advanced Settings</h2>

<ul>
    <li><strong>Blocked Hits Threshold:</strong> Define the minimum number of blocked hits before an IP is synchronized or evaluated by rules.</li>
    <li><strong>Block Scope:</strong> Choose between domain-specific or account-wide blocking.</li>
    <li><strong>Block Mode:</strong> Select the action for Cloudflare to take (e.g., Block, Challenge).</li>
    <li><strong>Cron Interval:</strong> Set how frequently the plugin checks for new blocked IPs and evaluates rules.</li>
    <li><strong>Rule Priorities:</strong> Assign priorities to your rules to control the order of evaluation. Higher priority numbers are evaluated first.</li>
    <li><strong>User Role Exclusions:</strong> Exclude specific WordPress user roles from traffic logging and rule evaluation.</li>
</ul>

<h2 id="faq">Frequently Asked Questions</h2>

<h3>How do I obtain my Cloudflare Zone ID and Account ID?</h3>

<p>
    <strong>Zone ID:</strong> Log into Cloudflare, select your domain, and find the Zone ID on the Overview page.<br />
    <strong>Account ID:</strong> Click on your profile in Cloudflare; the Account ID is listed there.
</p>

<h3>Can I use an API Token instead of the Global API Key?</h3>

<p>
    Yes, ensure the API Token has the necessary permissions for firewall access.
</p>

<h3>Is the plugin compatible with IPv6 addresses?</h3>

<p>
    Absolutely, the plugin supports both IPv4 and IPv6 addresses.
</p>

<h3>How does the plugin handle my API keys?</h3>

<p>
    All API keys are securely stored using WordPress's options API and are never exposed in plain text.
</p>

<h3>How do rule priorities work?</h3>

<p>
    Rule priorities determine the order in which your rules are evaluated. Rules with higher priority numbers are evaluated first. If traffic data matches a rule, the corresponding action is applied, and no further rules are evaluated for that IP address.
</p>

<h3>Can I see which rule blocked an IP?</h3>

<p>
    Yes, with the <strong>Rule Details in Blocked IPs</strong> feature, you can view the exact rule criteria and action that caused an IP to be blocked. This information is displayed in the Blocked IPs tab under the Rule Details column.
</p>

<h3>How does the automatic action application work?</h3>

<p>
    When traffic data is captured, the plugin immediately evaluates it against your defined rules. If a rule matches, the specified action is applied to the IP address without any manual intervention.
</p>

<h2 id="support">Support and Contribution</h2>

<p>
    <strong>Support:</strong> For assistance, please open an issue on our <a href="https://github.com/itcssec/ProActiveSecuritySuite/issues">GitHub Issues</a> page or contact us at <a href="mailto:info@itcs.services">info@itcs.services</a>.
</p>

<h2 id="license">License</h2>

<p>
    This project is licensed under the <a href="https://www.gnu.org/licenses/gpl-3.0.html">GNU General Public License v3.0</a>.
</p>

<hr />

<p>
    <em>Thank you for using ProActive Security Suite!</em> 
</p>


== Screenshots ==

1. The plugin main settings page

== Upgrade Notice ==

= 1.5.4 =

Plugin re-branded to Proactive Security Suite!

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

= 1.5.2 =

New Features:

Exclude User Roles from Captured Traffic Data (Premium Feature)

Description: Administrators can now select one or more WordPress user roles to exclude from traffic logging in the Captured Traffic Data. This feature enhances privacy and control by preventing the logging of traffic data for trusted users or specific roles, such as administrators or editors.
How to Use: Navigate to the plugin's settings page under the "Exclude Roles from Captured Traffic" section (available for premium users). Select the roles you wish to exclude from logging and save the settings.
Improvements:

AbuseIPDB Data Display Enhancement

Resolved an issue where AbuseIPDB data (Country Code, Usage Type, ISP, Confidence Score) was not displayed on every row for IP addresses appearing multiple times in the Captured Traffic table.
Now, when AbuseIPDB data is fetched for an IP address, all existing entries with that IP in the Captured Traffic table are updated to reflect the latest data.
Code Compliance and Optimization

Ensured all new code follows WordPress coding standards and best practices.
Implemented proper sanitization, validation, and use of WordPress APIs for enhanced security and performance.
Important Notes:

Premium Access Required: The new feature to exclude user roles from traffic logging is available only in the premium version of the plugin. Free users will see a notice indicating that the feature is available upon upgrading.

Action Required: After upgrading to version 1.5.2, premium users should review the new settings:

Go to the plugin settings page.
Under the "Exclude Roles from Captured Traffic" section, select the user roles you wish to exclude.
Save the settings to apply the changes.
No Impact on Existing Settings: Existing configurations and data are not affected by this update. However, reviewing the new settings is recommended to take full advantage of the new features.

Compatibility: This update is fully compatible with WordPress versions up to the latest release as of [Current Date].

Upgrade Instructions:

Backup Your Site: It's always good practice to back up your WordPress site before performing any updates.

Update the Plugin:

Navigate to your WordPress dashboard.
Go to Plugins > Installed Plugins.
Find "Wordfence to Cloudflare" in the list.
Click on "Update Now" to upgrade to version 1.5.2.
Review New Settings (Premium Users):

After the update, go to Settings > WTC Settings.
Navigate to the "Exclude Roles from Captured Traffic" section.
Select the roles to exclude and save your settings.

= 1.5.3 =

Added
Advanced Rule Builder and Evaluation System: Introduced a powerful rule evaluation engine that allows you to create custom security rules based on various criteria such as confidence score, whitelisted status, abusive status, and more. This enables automated decision-making and actions based on incoming traffic data.

Rule Priorities: Implemented a priority system for rules, allowing you to control the order in which rules are evaluated. Higher priority numbers are evaluated first, ensuring critical rules are applied before others.

Automatic Action Application: The plugin now automatically applies actions (e.g., block, challenge) to IP addresses that match your defined rules immediately after capturing traffic data.

Rule Details in Blocked IPs Table: Enhanced the Blocked IPs tab by adding a new column that displays detailed information about the rules that caused each IP address to be blocked, including criteria and actions taken.

Fixed
Database Schema Updates: Resolved issues with missing database columns (block_mode, rule_id, rule_details) by enhancing the table creation and update process. The plugin now correctly updates existing tables to include new columns without errors.

DataTables Initialization Issue: Fixed an issue where the DataTables plugin was being initialized multiple times, causing reinitialization errors. The plugin now ensures that DataTables are initialized correctly without conflicts.

Rule Evaluation Bug: Addressed a bug where rules were not being evaluated after capturing traffic data. The plugin now properly calls the rule evaluation function, ensuring that your rules are applied as expected.

= 1.5.4 =

Plugin re-branded to Proactive Security Suite!