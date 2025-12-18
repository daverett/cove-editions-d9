// js/src/CoveDocument.js
import React from "react";
import { CoveEdition } from "@performant-software/cove-edition";

const CoveDocument = ({ teiUrl }) => {
  const config = {
    teiUrl,
    tagVocabulary: [],
  };

  return (
    <div className="viewer-container">
      <CoveEdition config={config} />
    </div>
  );
};

export default CoveDocument;
