<h1>Welcome to the <em>ProActive Security Suite</em> Plugin Wiki</h1>
<img width="120" alt="pss-logo" src="https://github.com/user-attachments/assets/b657ffe7-3010-4e1d-9e83-81eec2bcd552"> 
<p>
    Enhance your WordPress website's security with the <strong>ProActive Security Suite</strong>. This powerful plugin offers advanced security features including automatic IP blocking, an advanced rule builder, traffic analysis, and seamless integration with services like <strong>Cloudflare</strong>, <strong>AbuseIPDB</strong>, <strong>Whatismybrowser.com</strong>, and now <strong>IPData</strong>. ProActive Security Suite provides proactive defense mechanisms to protect your site from malicious traffic and potential threats before they reach your server.
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
            <li><a href="#ipdata-integration">IPData Integration</a></li>
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
    Welcome to the <strong>ProActive Security Suite</strong> plugin! This comprehensive security solution enhances your website's protection by combining advanced threat detection, automated rule-based actions, and integrations with services like <strong>Cloudflare</strong>, <strong>AbuseIPDB</strong>, <strong>WhatIsMyBrowser</strong>, and <strong>IPData</strong>. By proactively analyzing traffic and applying custom security rules, ProActive Security Suite stops malicious traffic before it reaches your server, reducing load and enhancing performance.
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
    <li><strong>Advanced Rule Builder:</strong> Create custom security rules based on various criteria such as confidence score, whitelisted status, abusive status, IPData threat status, and more. Automate actions like blocking or challenging IPs based on these rules.</li>
    <li><strong>IPData Integration:</strong> Fetch threat intelligence from IPData, including tor usage, proxy, known attackers, and other threat indicators. Combine this data with AbuseIPDB and WhatIsMyBrowser data to build comprehensive multi-criteria rules for your traffic.</li>
    <li><strong>Rule Priorities:</strong> Assign priorities to your rules to control the order of evaluation. Higher priority numbers are evaluated first, allowing critical rules to take precedence.</li>
    <li><strong>Automatic Action Application:</strong> The plugin automatically applies actions to IPs that match your defined rules immediately after capturing traffic data.</li>
    <li><strong>Rule Details in Blocked IPs:</strong> View detailed information about which rules caused IPs to be blocked, including criteria and actions taken.</li>
    <li><strong>Captured Traffic Data:</strong> Log and analyze incoming traffic for enhanced security insights, leveraging data from multiple APIs (AbuseIPDB, WhatIsMyBrowser, IPData).</li>
    <li><strong>Exclude User Roles:</strong> Exclude specific WordPress user roles from traffic logging.</li>
    <li><strong>WhatIsMyBrowser.com API Integration:</strong> Advanced user agent analysis and detection capabilities.</li>
    <li><strong>Enhanced AbuseIPDB Integration:</strong> Automatic updates for all entries with the same IP address.</li>
    <li><strong>Priority Support:</strong> Access dedicated support for assistance and troubleshooting.</li>
    <li><strong>NEW - Traffic Insights:</strong> View your traffic at a glance by aggregating and displaying each IP address only once. This tab provides:
        <ul>
            <li><em>Aggregated IP Overview:</em> See first/last seen timestamps, total requests, and threat data from IPData in a single row per IP.</li>
            <li><em>Operating System & Browser Details:</em> Includes the most recent OS and software information from WhatIsMyBrowser.</li>
            <li><em>User Agent:</em> Quickly review the last user agent encountered for each IP.</li>
            <li><em>Professional Statistics:</em> Real-time stats on total unique IPs, average confidence scores, top countries, and more, all on one page.</li>
        </ul>
    </li>
</ul>
<img width="1163" alt="Screenshot 2024-12-21 at 14 28 25" src="https://github.com/user-attachments/assets/80ed56ce-cc95-441e-8490-6927153ffe9f" />

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

<img width="1245" alt="390847194-2b70c58b-6889-44b6-8f72-377f415b12a2" src="https://github.com/user-attachments/assets/f01a1ad4-b708-4c18-9959-9e80891406b9">

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

<img width="1242" alt="390847364-124d8ae7-91ba-4493-afb3-30e19a369a0b" src="https://github.com/user-attachments/assets/3bf0628e-582f-4a75-9e71-20726cc4c5b6">


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

<h3 id="ipdata-integration">IPData Integration (Premium)</h3>

<ol>
    <li><strong>Obtain API Key:</strong> Sign up at <a href="https://ipdata.co/">IPData</a> for an API key.</li>
    <li><strong>Enable Integration:</strong>
        <ul>
            <li>Enter your IPData API key in the plugin's settings.</li>
            <li>Enable the <em>IPData Lookup</em> option.</li>
        </ul>
    </li>
</ol>

<p>
    With IPData integration, the plugin fetches comprehensive threat intelligence for each IP, including indicators like:
</p>

<ul>
    <li><strong>Tor</strong></li>
    <li><strong>iCloud Relay</strong></li>
    <li><strong>Proxy</strong></li>
    <li><strong>Datacenter</strong></li>
    <li><strong>Anonymous User</strong></li>
    <li><strong>Known Attacker</strong></li>
    <li><strong>Known Abuser</strong></li>
    <li><strong>General Threat</strong></li>
    <li><strong>Bogon</strong></li>
</ul>

<p>
    These fields can be combined with AbuseIPDB and WhatIsMyBrowser data in the rule builder. If an IP has previously been queried, subsequent visits reuse the stored IPData information without making additional API requests, ensuring efficient lookups.
</p>

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
    Access detailed logs under the <em>Captured Traffic Data</em> tab. Analyze user agents, request methods, threat intelligence from IPData, and more. Exclude specific user roles from logging in the settings.
</p>

<p>
    <strong>Note on Caching:</strong> If full-page caching or a CDN is serving cached responses, some traffic may not be captured because WordPress (and thus this plugin) may not run on every request. Consider adjusting your caching strategy or using a JavaScript-driven approach (e.g., a small script that calls a logged endpoint) if capturing all traffic is critical.
</p>

<h3 id="advanced-rule-builder">Advanced Rule Builder (Premium)</h3>

<p>
    The plugin features a powerful <strong>Rule Builder</strong> that allows you to create custom security rules based on various criteria. You can define rules using conditions such as:
</p>

<ul>
    <li><strong>Confidence Score (AbuseIPDB)</strong></li>
    <li><strong>Is Whitelisted (AbuseIPDB)</strong></li>
    <li><strong>Is Abusive (WhatIsMyBrowser)</strong></li>
    <li><strong>IPData Threat Indicators (Tor, Proxy, Known Attacker, etc.)</strong></li>
    <li><strong>Custom Criteria:</strong> Combine fields from multiple APIs to create complex, multi-dimensional rules.</li>
</ul>

<p>
    Each rule can be assigned an <strong>Action</strong> (e.g., Block, Managed Challenge) that will be applied to IPs matching the rule. You can also assign a <strong>Priority</strong> to control the order in which rules are evaluated.
</p>

<img width="1240" alt="390848297-7c3c405b-7a5f-4e86-ad35-f0128c288fcf" src="https://github.com/user-attachments/assets/52dcb275-3d70-4ff8-ab8a-3483dc581b94">
<img width="1223" alt="Screenshot 2024-12-07 at 16 00 16" src="https://github.com/user-attachments/assets/4b3e4843-9e56-4249-adc0-cee8c0464aa1">

<h3 id="rule-details-in-blocked-ips">Rule Details in Blocked IPs (Premium)</h3>

<p>
    The <strong>Blocked IPs</strong> tab now includes a <em>Rule Details</em> column that displays comprehensive information about the rules that caused IPs to be blocked. This includes:
</p>

<ul>
    <li><strong>Criteria:</strong> Specific conditions from AbuseIPDB, WhatIsMyBrowser, and IPData threat fields that were met.</li>
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
