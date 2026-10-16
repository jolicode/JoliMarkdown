import { diffWordsWithSpace } from "diff/lib/diff/word";
import { structuredPatch } from "diff/lib/patch/create";
import createPhp from "./build/php-web.mjs";

const form = document.getElementById("markdown_fixer");
const examples = document.getElementById("markdown_fixer_examples");
const editor = document.getElementById("markdown_fixer_editor");
const source = document.getElementById("markdown_fixer_source");
const result = document.getElementById("markdown_fixer_result");
const diff = document.getElementById("markdown_fixer_diff");
const summary = document.getElementById("markdown_fixer_summary");
const copy = document.getElementById("markdown_fixer_copy");
const status = document.getElementById("php_status");

copyOnClick(document.getElementById("composer_copy"), () => "composer require jolicode/jolimarkdown");
copyOnClick(copy, () => result.value);

for (const tab of document.querySelectorAll("[role=tab]")) {
    tab.addEventListener("click", () => {
        for (const other of document.querySelectorAll("[role=tab]")) {
            const selected = other === tab;
            other.setAttribute("aria-selected", selected);
            document.getElementById(other.getAttribute("aria-controls")).classList.toggle("hidden", !selected);
        }
    });
}

const php = await createPhp().catch((error) => {
    setStatus("error", "PHP failed to load");
    summary.textContent = "PHP failed to load";
    showMessage(`${error}`, true);
    throw error;
});

setStatus("ready", "Runs in your browser: PHP compiled to WebAssembly");

function copyOnClick(button, text) {
    button.addEventListener("click", async () => {
        const label = button.querySelector("[data-label]") ?? button;
        const initial = label.textContent;
        await navigator.clipboard.writeText(text());
        label.textContent = "Copied";
        setTimeout(() => (label.textContent = initial), 1500);
    });
}

function setStatus(state, label) {
    status.dataset.state = state;
    status.querySelector("[data-label]").textContent = label;
}

async function runFixer(markdown) {
    const { exitCode, stdout, stderr } = await php.runFile("/app/fix.php", {
        stdin: JSON.stringify({
            markdown,
            options: {
                unorderedListMarker: form.elements.unorderedListMarker.value,
                preferAsteriskOverUnderscore: form.elements.emphasisMarker.value === "*",
                internalDomains: form.elements.internalDomains.value.split(/[\s,]+/),
            },
        }),
    });

    return exitCode === 0 ? { output: stdout } : { error: (stderr || stdout).trim() };
}

function patchOf(before, after) {
    const patch = structuredPatch("before", "after", before, after, "", "", { context: 3 });
    const lines = patch.hunks.flatMap((hunk) => hunk.lines);
    // A change is a run of changed lines: a hunk can hold several when they are close to each other
    const isChanged = (line) => "+-\\".includes(line?.[0]);

    return {
        hunks: patch.hunks,
        changes: patch.hunks.flatMap((hunk) => hunk.lines.filter((line, index) => isChanged(line) && !isChanged(hunk.lines[index - 1]))).length,
        added: lines.filter((line) => line[0] === "+").length,
        removed: lines.filter((line) => line[0] === "-").length,
    };
}

async function fix() {
    if (!editor.value.trim()) {
        result.value = "";
        copy.disabled = true;
        summary.textContent = "Nothing to fix yet";
        showMessage("Paste some Markdown on the left, or pick an example above.");

        return;
    }

    const { output, error } = await runFixer(editor.value);
    if (error !== undefined) {
        result.value = "";
        copy.disabled = true;
        summary.textContent = "The fixer failed";
        showMessage(error || "The fixer stopped without an error message.", true);

        return;
    }

    result.value = output;
    copy.disabled = false;
    renderDiff(patchOf(editor.value, output));
}

function renderDiff({ hunks, changes, added, removed }) {
    if (hunks.length === 0) {
        summary.textContent = "Nothing to fix";
        showMessage("This Markdown is already clean: the fixer left it as is.");

        return;
    }

    summary.replaceChildren(
        `${changes} ${changes > 1 ? "changes" : "change"} `,
        counts(added, removed),
    );

    diff.replaceChildren(...hunks.flatMap((hunk) => {
        let oldLine = hunk.oldStart;
        let newLine = hunk.newStart;
        const marks = wordMarks(hunk.lines);

        return [
            element("div", "diff-hunk", `@@ -${hunk.oldStart},${hunk.oldLines} +${hunk.newStart},${hunk.newLines} @@`),
            ...hunk.lines.map((line, index) => {
                const sign = line[0];
                if (sign === "\\") {
                    return element("div", "diff-meta", line.slice(2));
                }

                const row = element("div", { "+": "diff-line is-added", "-": "diff-line is-removed" }[sign] ?? "diff-line");
                const content = element("span", "");
                content.append(...(marks.get(index) ?? [line.slice(1)]));
                row.append(
                    element("span", "", sign === "+" ? "" : `${oldLine++}`),
                    element("span", "", sign === "-" ? "" : `${newLine++}`),
                    element("span", "", sign.trim()),
                    content,
                );

                return row;
            }),
        ];
    }));
}

// Pairs each removed line with the added line at the same rank in the block that follows,
// and marks the words that changed, unless the lines have too little in common to compare
function wordMarks(lines) {
    const marks = new Map();
    for (let start = 0; start < lines.length;) {
        let end = start;
        while (lines[end]?.[0] === "-") end++;
        let addedEnd = end;
        while (lines[addedEnd]?.[0] === "+") addedEnd++;

        for (let rank = 0; rank < Math.min(end - start, addedEnd - end); rank++) {
            const before = lines[start + rank].slice(1);
            const after = lines[end + rank].slice(1);
            const parts = diffWordsWithSpace(before, after);
            const kept = parts.filter((part) => !part.added && !part.removed).reduce((length, part) => length + part.value.length, 0);
            if (kept >= Math.max(before.length, after.length) * 0.4) {
                marks.set(start + rank, parts.filter((part) => !part.added).map((part) => part.removed ? element("del", "", part.value) : part.value));
                marks.set(end + rank, parts.filter((part) => !part.removed).map((part) => part.added ? element("ins", "", part.value) : part.value));
            }
        }
        start = Math.max(addedEnd, start + 1);
    }

    return marks;
}

function showMessage(text, isError = false) {
    diff.replaceChildren(element("p", isError ? "diff-message is-error" : "diff-message", text));
}

function counts(added, removed) {
    const wrapper = element("span", "ml-1 inline-flex gap-2 text-xs font-medium tabular-nums");
    wrapper.append(
        element("ins", "text-blue-400 no-underline", `+${added}`),
        element("del", "text-rose-400 no-underline", `−${removed}`),
    );

    return wrapper;
}

function element(tag, className, text = "") {
    const node = document.createElement(tag);
    node.className = className;
    node.textContent = text;

    return node;
}

function exampleInput(name) {
    return php.FS.readFile(`/app/examples/${name}-in.md`, { encoding: "utf8" });
}

function loadExample(radio) {
    radio.checked = true;
    editor.value = exampleInput(radio.value);
    const tag = radio.parentElement.querySelector(".example-tag");
    source.replaceChildren("Example: ", element("span", tag.className.includes("code") ? "code" : "", tag.textContent));
    fix();
}

let timeout;
form.addEventListener("input", (event) => {
    const checked = examples.querySelector("input:checked");
    if (event.target === editor && checked) {
        checked.checked = false;
        source.textContent = "Your own Markdown";
    }
    clearTimeout(timeout);
    timeout = setTimeout(fix, 150);
});

examples.addEventListener("change", (event) => loadExample(event.target));

for (const radio of examples.querySelectorAll("input")) {
    radio.disabled = false;
}
loadExample(examples.querySelector("input"));
