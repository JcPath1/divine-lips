import { MyComposition } from "./Composition";
import { SunlitComposition } from "./Sunlit";
import { ReelCompositions } from "./Reels";

export const RemotionRoot: React.FC = () => {
  return (
    <>
      <MyComposition />
      <SunlitComposition />
      <ReelCompositions />
    </>
  );
};
