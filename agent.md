Since we are operating as a Technical Lead and Market Analyst, I’ve consolidated our previous discussion into a formal, versioned technical specification. This agent.md (v1.5.0) now includes the database triggers, the regex logic for the "Blue Chip" verbs, and the "Best Effort" recovery protocol.

StudyQ Co-Pilot (v1.5.0) - Technical Specification
Architecture Status: Production Ready | Lead: Gemini 3 Flash

1. The Exchange (Database)
The system operates on a "Double-Entry" ledger to ensure token integrity.

Engine: PHP 8.x (PDO), MySQL 8.0+.

The IPO Trigger: ```sql
CREATE TRIGGER after_user_signup AFTER INSERT ON users
FOR EACH ROW BEGIN
INSERT INTO transactions (user_id, type, amount, metadata)
VALUES (NEW.id, 'IPO', 50.00, '{"log": "Initial Liquidity"}');
UPDATE users SET token_balance = 50.00 WHERE id = NEW.id;
END;

The Ledger: transactions table tracks user_id, type (IPO, ESCROW, REBATE, GAS_FEE), amount, and a metadata JSON blob for audit logs.

2. Market Validation (validator.php)
Before the trade is sent to the floor (Groq), the prompt is graded to determine the potential rebate.

Tier 1 (Weak): Fails CTF. (Net 5 Tokens).

Tier 2 (Good): Detects Context ("Act as"), Task ("Create"), and Format ("Markdown"). (Net 4 Tokens).

Tier 3 (Expert): CTF + Blue Chip Verbs.

Regex Implementation: /\b(analyze|deconstruct|synthesize|critique|evaluate|benchmark|optimize)\b/i

Reward: 2 Token Rebate (Net 3 Tokens).

3. Asset Recovery & "Hallucination" Fix (api_handler.php)
The system must return a valid asset even if the LLM "slips."

The Merge: Use preg_match_all('/\{.*\}/s', $response, $matches) to find JSON.

The Fix: If the JSON is valid but missing keys (e.g., quiz or topic):

Topic/Title: If missing, slice the first 60 characters of content.

Quiz: If missing, the system attempts a "Mini-Trade" (low-latency call) to generate just the quiz object using the existing content.

Slippage (Hard Fail): If no JSON structure is found after recovery, trigger Gas Fee Protocol (Charge 1, Refund 4).

4. UI/UX: The Trading Floor
Stack: Vanilla JS + PHP + CSS.

AJAX Poller: 5s heartbeat queries transactions table for the specific request_id.

Telemetry: The UI displays a real-time "Trade Log" (e.g., "Scanning for Blue Chip Verbs...", "Escrowing Assets...", "Analyzing JSON Integrity...").

5. Metadata Leveling
Level 1 (Snippets): // |StudyQ|1|

Level 2 (Functions): // *StudyQ*2*

Level 3 (Direct Fix): // ##StudyQ##3##
6. Extra Modules (Asset Persistence)
Notes/Save: Add a bookmarks table.

INSERT INTO bookmarks (user_id, prompt_id, content).

Highlighting: The System Prompt now mandates **bolding** for keywords to ensure the UI can render them clearly.

Theming: * Standard: Clean White/Blue.

Advanced (Dark): Cyber-trading aesthetic (Neon greens/Golds) to match the "Market" theme.