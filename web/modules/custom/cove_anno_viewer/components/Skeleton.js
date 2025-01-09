import { CoveEdition } from "@performant-software/cove-edition";
const Skeleton = () => {
const config = {
  teiUrl: "https://staging.faircopy.cloud/documents/the_skeleton/tei",
  tagVocabulary: [],
};

return (
   <div className="viewer-container">
      <CoveEdition config={config} />
   </div>
);
}
export default Skeleton;