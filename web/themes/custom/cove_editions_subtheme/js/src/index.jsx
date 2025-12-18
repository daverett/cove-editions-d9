// js/src/index.jsx
console.log("React bundle loaded, looking for .cove-react-mount elements");
console.log(">>> BUNDLE VERSION XYZ <<<"); // add this at the top


import React from "react";
import { createRoot } from "react-dom/client";
import CoveDocument from "./CoveDocument"; // or Skeleton if you kept that name

function bootCoveDocuments() {
  // Find ALL mount points on the page
  const mounts = document.querySelectorAll(".cove-react-mount[data-cove-tei-url]");

  if (!mounts.length) {
    console.warn("No .cove-react-mount elements found on this page.");
    return;
  }

  mounts.forEach((el) => {
    const teiUrl = el.dataset.coveTeiUrl;
    if (!teiUrl) {
      console.warn("Missing data-cove-tei-url on element", el);
      return;
    }

    const root = createRoot(el);
    root.render(<CoveDocument teiUrl={teiUrl} />);
  });
}

document.addEventListener("DOMContentLoaded", bootCoveDocuments);
