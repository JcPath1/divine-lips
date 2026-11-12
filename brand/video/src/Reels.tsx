import {
  AbsoluteFill,
  CanvasImage,
  Composition,
  Easing,
  Interactive,
  interpolate,
  staticFile,
  useVideoConfig,
} from "remotion";
import { Backdrop, Site, useWordFrame, useWordsFade } from "./Loop";
import { loadFont as loadDisplay } from "@remotion/google-fonts/CormorantGaramond";
import { loadFont as loadText } from "@remotion/google-fonts/Jost";

// Organic batch 1: 9:16 reels for Glimmer Kiss.
// Each one is a 5-second AI clip (fal Kling) made from our real bottle photo, with the logo already on the bottle.
// Words are added here. Keep all words between y=250 and y=1580 (Instagram covers the rest with buttons).
const { fontFamily: display } = loadDisplay("normal", {
  weights: ["600"],
  subsets: ["latin"],
});
loadDisplay("italic", { weights: ["500"], subsets: ["latin"] });
const { fontFamily: text } = loadText("normal", {
  weights: ["400", "500", "600"],
  subsets: ["latin"],
});

const INK = "#1a1a1a";
const POP = "#e6337f";
const EASE = Easing.bezier(0.16, 1, 0.3, 1);
const CLAMP = {
  extrapolateLeft: "clamp",
  extrapolateRight: "clamp",
} as const;
const COVER = {
  position: "absolute",
  inset: 0,
  width: "100%",
  height: "100%",
  objectFit: "cover",
} as const;

type ReelProps = {
  // "macro" or "studio": picks the clip files in public/ (see Loop.tsx)
  name: string;
  // true = the longer website version (slower words, longer hold, words fade out, smooth repeat)
  site?: boolean;
  // Space between the lead lines. Raise it when a "y" or "g" runs into the line below.
  leadLineHeight?: number;
  // true = "pink." pops in fast, like a burst, not a slow wipe
  burst?: boolean;
  // Moves and enlarges the picture (clip + still bottle together) so the bottle sits on a phi line.
  // A CSS transform, measured from the top left corner. Example: "translate(0px, -153px) scale(1.18)".
  stage?: string;
  leadLines: string[];
  leadLeft: number;
  leadTop: number;
  leadSize: number;
  bigLeft: number;
  bigTop: number;
  bigSize: number;
  infoLeft: number;
  infoTop: number;
  infoAlign: "left" | "right";
  infoWidth: number;
  infoSize: number;
  // true = price and website on one line (used when the block sits under "pink.")
  infoInline?: boolean;
  // A still picture of the bottle laid over the clip, so the glitter inside does not flow (JC, 11 Nov:
  // moving glitter looks AI-made). Only the light around the bottle moves.
  freeze?: string;
  // false = no words at all (the clean bottle video for the product page)
  words?: boolean;
};

export const ReelCompositions = () => {
  return (
    <>
      <Composition
        id="ReelMacro"
        component={Reel}
        durationInFrames={156}
        fps={24}
        width={1080}
        height={1920}
        defaultProps={{
          name: "macro",
          leadLines: ["Wet.", "Clear.", "Then"],
          leadLeft: 70,
          leadTop: 262,
          leadSize: 124,
          bigLeft: 330,
          bigTop: 1045,
          bigSize: 440,
          // JC, 11 Nov: the words go under "pink." on the right, on the light surface, not in the shadow.
          infoLeft: 380,
          infoTop: 1440,
          infoAlign: "right",
          infoWidth: 640,
          infoSize: 34,
          infoInline: true,
          freeze: "macro-freeze.png",
          burst: true,
        }}
      />
      <Composition
        id="ReelStudio"
        component={Reel}
        durationInFrames={156}
        fps={24}
        width={1080}
        height={1920}
        defaultProps={{
          name: "studio",
          leadLines: ["Everyone gets", "a different"],
          leadLeft: 70,
          leadTop: 262,
          leadSize: 104,
          bigLeft: 50,
          bigTop: 510,
          bigSize: 272,
          infoLeft: 70,
          infoTop: 898,
          infoAlign: "left",
          infoWidth: 340,
          infoSize: 40,
          freeze: "studio-freeze.png",
          // Phi layout (JC, 11 Nov): the picture is enlarged 1.18x so the bottle's center line sits on the
          // right phi line (x = 667 of 1080), not in the middle. Type sizes step by phi: 40, 104, 272.
          stage: "translate(0px, -153px) scale(1.18)",
          leadLineHeight: 1.12,
          burst: true,
        }}
      />
      <Composition
        id="ReelMacroSite"
        component={Reel}
        durationInFrames={312}
        fps={24}
        width={1080}
        height={1920}
        defaultProps={{
          site: true,
          name: "macro",
          leadLines: ["Wet.", "Clear.", "Then"],
          leadLeft: 70,
          leadTop: 262,
          leadSize: 124,
          bigLeft: 330,
          bigTop: 1250,
          bigSize: 440,
          // Website cut only: "pink." and the small words sit lower, clear of GLIMMER BLISS on the bottle.
          // (The social cut cannot go this low: Instagram covers everything below y=1580.)
          // JC, 11 Nov: the words go under "pink." on the right, on the light surface, not in the shadow.
          infoLeft: 380,
          infoTop: 1642,
          infoAlign: "right",
          infoWidth: 640,
          infoSize: 34,
          infoInline: true,
          freeze: "macro-freeze.png",
          burst: true,
        }}
      />
      <Composition
        id="ReelStudioSite"
        component={Reel}
        durationInFrames={312}
        fps={24}
        width={1080}
        height={1920}
        defaultProps={{
          site: true,
          name: "studio",
          leadLines: ["Everyone gets", "a different"],
          leadLeft: 70,
          leadTop: 262,
          leadSize: 104,
          bigLeft: 50,
          bigTop: 510,
          bigSize: 272,
          infoLeft: 70,
          infoTop: 898,
          infoAlign: "left",
          infoWidth: 340,
          infoSize: 40,
          freeze: "studio-freeze.png",
          // Phi layout (JC, 11 Nov): the picture is enlarged 1.18x so the bottle's center line sits on the
          // right phi line (x = 667 of 1080), not in the middle. Type sizes step by phi: 40, 104, 272.
          stage: "translate(0px, -153px) scale(1.18)",
          leadLineHeight: 1.12,
          burst: true,
        }}
      />
    </>
  );
};

const Lead = ({
  lines,
  left,
  top,
  size,
  lineHeight = 0.96,
}: {
  lines: string[];
  left: number;
  top: number;
  size: number;
  lineHeight?: number;
}) => {
  const frame = useWordFrame();
  const fade = useWordsFade();
  const { fps } = useVideoConfig();
  return (
    <div
      style={{
        position: "absolute",
        left,
        top,
        fontFamily: display,
        fontWeight: 600,
        fontSize: size,
        lineHeight,
        letterSpacing: "-0.02em",
        color: INK,
        opacity: fade,
      }}
    >
      {lines.map((line, i) => {
        const start = (0.3 + i * 0.55) * fps;
        return (
          <div
            key={line}
            style={{
              opacity: interpolate(
                frame,
                [start, start + 0.5 * fps],
                [0, 1],
                CLAMP,
              ),
              translate: interpolate(
                frame,
                [start, start + 0.8 * fps],
                ["0px 36px", "0px 0px"],
                { ...CLAMP, easing: EASE },
              ),
            }}
          >
            {line}
          </div>
        );
      })}
    </div>
  );
};

const BigPink = ({
  left,
  top,
  size,
  burst,
}: {
  left: number;
  top: number;
  size: number;
  burst?: boolean;
}) => {
  const frame = useWordFrame();
  const fade = useWordsFade();
  const { fps } = useVideoConfig();
  // Normal: a slow wipe from left to right. Burst: the word is there in a blink and pops up in size.
  const hidden = interpolate(
    frame,
    [2.3 * fps, (burst ? 2.55 : 3.5) * fps],
    [100, 0],
    {
      ...CLAMP,
      easing: burst
        ? Easing.out(Easing.cubic)
        : Easing.bezier(0.65, 0, 0.35, 1),
    },
  );
  const pop = burst
    ? interpolate(frame, [2.3 * fps, 2.75 * fps], [0.84, 1], {
        ...CLAMP,
        easing: Easing.out(Easing.back(2.4)),
      })
    : 1;
  return (
    <Interactive.Div
      name="pink."
      style={{
        position: "absolute",
        left,
        top,
        fontFamily: display,
        fontStyle: "italic",
        fontWeight: 500,
        fontSize: size,
        lineHeight: 1,
        letterSpacing: "-0.03em",
        whiteSpace: "nowrap",
        color: POP,
        mixBlendMode: "multiply",
        paddingRight: 60,
        scale: pop,
        transformOrigin: "20% 70%",
        clipPath: `inset(-10% ${hidden}% -30% -5%)`,
        opacity:
          fade * interpolate(frame, [2.3 * fps, 2.4 * fps], [0, 1], CLAMP),
      }}
    >
      pink.
    </Interactive.Div>
  );
};

const Info = ({
  left,
  top,
  align,
  width,
  size,
  inline,
}: {
  left: number;
  top: number;
  align: "left" | "right";
  width: number;
  size: number;
  inline?: boolean;
}) => {
  const frame = useWordFrame();
  const fade = useWordsFade();
  const { fps } = useVideoConfig();
  return (
    <div
      style={{
        position: "absolute",
        left,
        top,
        width,
        textAlign: align,
        fontFamily: text,
        color: INK,
        opacity: fade,
      }}
    >
      <Interactive.Div
        name="pH lines"
        style={{
          opacity: interpolate(frame, [3.7 * fps, 4.3 * fps], [0, 1], CLAMP),
          translate: interpolate(
            frame,
            [3.7 * fps, 4.5 * fps],
            ["0px 20px", "0px 0px"],
            { ...CLAMP, easing: EASE },
          ),
        }}
      >
        <div style={{ fontSize: size, fontWeight: 600, lineHeight: 1.15 }}>
          pH color-changing lip gloss
        </div>
        <div
          style={{
            fontSize: size,
            fontWeight: 400,
            lineHeight: 1.2,
            marginTop: 8,
          }}
        >
          Your lips’ natural pH picks your pink.
        </div>
      </Interactive.Div>
      <Interactive.Div
        name="Price and site"
        style={{
          fontSize: size - 4,
          fontWeight: 500,
          lineHeight: 1.3,
          marginTop: 14,
          opacity: interpolate(frame, [4.6 * fps, 5.1 * fps], [0, 1], CLAMP),
        }}
      >
        <b style={{ fontWeight: 600 }}>Glimmer Kiss $15</b>
        {inline ? <span>&nbsp;&nbsp;&nbsp;</span> : <br />}
        glimmerbliss.com
      </Interactive.Div>
    </div>
  );
};

export const Reel: React.FC<ReelProps> = (props) => {
  return (
    <Site.Provider value={{ pace: props.site ? 1.5 : 1, loop: !!props.site }}>
      <AbsoluteFill style={{ backgroundColor: "#f7c9d4" }}>
        <AbsoluteFill
          style={{ transformOrigin: "0 0", transform: props.stage ?? "none" }}
        >
          <Backdrop name={props.name} />
          {props.freeze ? (
            <CanvasImage src={staticFile(props.freeze)} style={COVER} />
          ) : null}
        </AbsoluteFill>
        {props.words === false ? null : (
          <>
            <Lead
              lines={props.leadLines}
              left={props.leadLeft}
              top={props.leadTop}
              size={props.leadSize}
              lineHeight={props.leadLineHeight}
            />
            <BigPink
              left={props.bigLeft}
              top={props.bigTop}
              size={props.bigSize}
              burst={props.burst}
            />
            <Info
              left={props.infoLeft}
              top={props.infoTop}
              align={props.infoAlign}
              width={props.infoWidth}
              size={props.infoSize}
              inline={props.infoInline}
            />
          </>
        )}
      </AbsoluteFill>
    </Site.Provider>
  );
};
