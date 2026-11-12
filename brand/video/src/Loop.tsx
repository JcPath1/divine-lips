import React, { createContext, useContext } from "react";
import {
  CanvasImage,
  interpolate,
  Sequence,
  staticFile,
  useCurrentFrame,
  useVideoConfig,
} from "remotion";
import { Video } from "@remotion/media";

// Website versions of the videos (JC, 11 Nov 2026):
//  - the words come in slower (pace 1.5 = one and a half times as slow),
//  - the finished picture stays up about 3 seconds longer,
//  - the words fade out before the video starts again,
//  - the clip plays forward, then backward, so the last picture is the same as the first: no jump on repeat.
// Social versions keep site = false: 6.5 seconds, words at normal speed.
export const Site = createContext({ pace: 1, loop: false });

// The clock the words use. Slower when pace is above 1.
export const useWordFrame = () => useCurrentFrame() / useContext(Site).pace;

// 1 while the words are showing, down to 0 just before the video repeats.
export const useWordsFade = () => {
  const frame = useCurrentFrame();
  const { fps, durationInFrames } = useVideoConfig();
  const { loop } = useContext(Site);
  if (!loop) {
    return 1;
  }
  return interpolate(
    frame,
    [durationInFrames - 0.95 * fps, durationInFrames - 0.2 * fps],
    [1, 0],
    { extrapolateLeft: "clamp", extrapolateRight: "clamp" },
  );
};

const COVER = {
  position: "absolute",
  inset: 0,
  width: "100%",
  height: "100%",
  objectFit: "cover",
} as const;

// name = "macro", "studio" or "sunlit". Files in public/: <name>-clip.mp4, <name>-clip-rev.mp4 (the same clip
// backward), <name>-first.png, <name>-last.png. Clips are 5 seconds at 24 fps: never slow them down, that
// repeats frames and the shadows stutter.
export const Backdrop: React.FC<{ name: string }> = ({ name }) => {
  const { fps } = useVideoConfig();
  const { loop } = useContext(Site);
  return (
    <>
      <CanvasImage
        src={staticFile(`${name}-${loop ? "first" : "last"}.png`)}
        style={COVER}
      />
      <Sequence durationInFrames={5 * fps}>
        <Video src={staticFile(`${name}-clip.mp4`)} muted style={COVER} />
      </Sequence>
      {loop ? (
        <Sequence from={5 * fps} durationInFrames={5 * fps}>
          <Video src={staticFile(`${name}-clip-rev.mp4`)} muted style={COVER} />
        </Sequence>
      ) : null}
    </>
  );
};
