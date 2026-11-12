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

// Glimmer Kiss "sunlit macro" ad, 4:5. Same layout as the approved still:
// ads/campaign-test/01-sunlit-macro-instagram-4x5.png
// The moving picture is an AI clip (fal Kling) made from that still, with the logo already on the bottle.
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

export const SunlitComposition = () => {
  return (
    <>
      <Composition
        id="GlimmerKissSunlit"
        component={GlimmerKissSunlit}
        durationInFrames={156}
        fps={24}
        width={1080}
        height={1350}
        defaultProps={{ site: false }}
      />
      <Composition
        id="GlimmerKissSunlitSite"
        component={GlimmerKissSunlit}
        durationInFrames={312}
        fps={24}
        width={1080}
        height={1350}
        defaultProps={{ site: true }}
      />
    </>
  );
};

// One word of the headline. `at` is when it starts to show, in seconds on the words' clock.
const Word: React.FC<{ at: number; children: React.ReactNode }> = ({
  at,
  children,
}) => {
  const frame = useWordFrame();
  const { fps } = useVideoConfig();
  return (
    <span
      style={{
        display: "inline-block",
        opacity: interpolate(
          frame,
          [at * fps, (at + 0.35) * fps],
          [0, 1],
          CLAMP,
        ),
        translate: interpolate(
          frame,
          [at * fps, (at + 0.6) * fps],
          ["0px 22px", "0px 0px"],
          { ...CLAMP, easing: EASE },
        ),
      }}
    >
      {children}
    </span>
  );
};

const Lead = () => {
  const frame = useWordFrame();
  const fade = useWordsFade();
  const { fps } = useVideoConfig();
  return (
    <div
      style={{
        position: "absolute",
        left: 64,
        top: 214,
        fontFamily: display,
        fontWeight: 600,
        fontSize: 104,
        lineHeight: 0.95,
        letterSpacing: "-0.02em",
        color: INK,
        opacity: fade,
      }}
    >
      {/* JC, 11 Nov: the words come in one at a time, as if being read. */}
      <Interactive.Div name="Goes on clear">
        <Word at={0.4}>Goes</Word> <Word at={0.8}>on</Word>
        <br />
        <Word at={1.2}>clear.</Word>
      </Interactive.Div>
      <Interactive.Div
        name="Turns"
        style={{
          opacity: interpolate(frame, [1.8 * fps, 2.25 * fps], [0, 1], CLAMP),
          translate: interpolate(
            frame,
            [1.8 * fps, 2.45 * fps],
            ["0px 36px", "0px 0px"],
            { ...CLAMP, easing: EASE },
          ),
        }}
      >
        Turns
      </Interactive.Div>
    </div>
  );
};

const BigPink = () => {
  const frame = useWordFrame();
  const fade = useWordsFade();
  const { fps } = useVideoConfig();
  const hidden = interpolate(frame, [2.3 * fps, 3.5 * fps], [100, 0], {
    ...CLAMP,
    easing: Easing.bezier(0.65, 0, 0.35, 1),
  });
  return (
    <Interactive.Div
      name="pink."
      style={{
        position: "absolute",
        left: 84,
        top: 770,
        fontFamily: display,
        fontStyle: "italic",
        fontWeight: 500,
        fontSize: 470,
        lineHeight: 1,
        letterSpacing: "-0.03em",
        whiteSpace: "nowrap",
        color: POP,
        mixBlendMode: "multiply",
        paddingRight: 60,
        clipPath: `inset(-10% ${hidden}% -30% -5%)`,
        opacity:
          fade * interpolate(frame, [2.3 * fps, 2.4 * fps], [0, 1], CLAMP),
      }}
    >
      pink.
    </Interactive.Div>
  );
};

const Info = () => {
  const frame = useWordFrame();
  const fade = useWordsFade();
  const { fps } = useVideoConfig();
  return (
    <div
      style={{
        position: "absolute",
        left: 300,
        bottom: 40,
        fontFamily: text,
        color: INK,
        whiteSpace: "nowrap",
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
        <div style={{ fontSize: 35, fontWeight: 600, lineHeight: 1.2 }}>
          pH color-changing lip gloss
        </div>
        <div style={{ fontSize: 30, fontWeight: 400, marginBottom: 8 }}>
          Your lips’ natural pH picks your pink.
        </div>
      </Interactive.Div>
      <Interactive.Div
        name="Price and site"
        style={{
          fontSize: 26,
          fontWeight: 500,
          lineHeight: 1.3,
          opacity: interpolate(frame, [4.6 * fps, 5.1 * fps], [0, 1], CLAMP),
        }}
      >
        <b style={{ fontWeight: 600 }}>Glimmer Kiss $15</b> &nbsp;
        glimmerbliss.com
      </Interactive.Div>
    </div>
  );
};

export const GlimmerKissSunlit: React.FC<{ site?: boolean }> = ({ site }) => {
  return (
    <Site.Provider value={{ pace: site ? 1.5 : 1, loop: !!site }}>
      <AbsoluteFill style={{ backgroundColor: "#f7c9d4" }}>
        <Backdrop name="sunlit" />
        {/* Still bottle over the clip: no flowing glitter, logo in the locked position and right way up. */}
        <CanvasImage
          src={staticFile("sunlit-freeze.png")}
          style={{
            position: "absolute",
            inset: 0,
            width: "100%",
            height: "100%",
            objectFit: "cover",
          }}
        />
        <Lead />
        <BigPink />
        <Info />
      </AbsoluteFill>
    </Site.Provider>
  );
};
