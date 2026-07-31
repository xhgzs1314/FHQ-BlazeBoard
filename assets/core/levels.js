(function (root) {
  "use strict";
  const LEVELS = [
    {
      id: "t1", chapter: 1, name: "初阵：军棋直取",
      hint: "军棋横纵斜均可走，步长 3 起；敌方军棋越少，你的军棋走得越远。选中红军棋，吃掉右侧的蓝军棋。",
      ai: 3,
      goal: {type: "slay", side: "red", target: "general", moveLimit: 1},
      fen: "FHQ1 14PK14/30/30/30/30/30/30/30/30/30/10G2g16/30/30/30/30/30/30/30/30/30/30/30/30/30/30/30/30/30/14pk14/30 r 8 - - - - - -",
      solution: [{ fc: 10, fr: 10, c: 13, r: 10 }],
    },
    {
      id: "t2", chapter: 1, name: "盾之侧翼",
      hint: "对方子棋在场时，盾棋只能被「横向」吃掉。上方那枚军棋纵向攻不进，请用左侧军棋沿同一行推进。",
      ai: 3,
      goal: {type: "slay", side: "red", target: "shield", moveLimit: 1},
      fen: "FHQ1 14PK14/30/30/30/30/30/30/10G19/30/30/6G3d19/30/30/30/30/30/30/30/30/30/30/30/30/30/30/30/30/30/14pk14/30 r 9 - - - - - -",
      solution: [{ fc: 6, fr: 10, c: 10, r: 10 }],
    },
    {
      id: "t3", chapter: 1, name: "探棋开路",
      hint: "棋子多于 20 枚时受迷雾与许可区约束：只有探棋、本方核心区的棋子能自由行动。先把探棋移到军棋旁，许可区与视野一起覆盖它，军棋才能出手。",
      ai: 3,
      goal: {type: "slay", side: "red", target: "general", count: 1, moveLimit: 2},
      fen: "FHQ1 14PK14/30/30/30/30/30/30/30/30/30/10G2g16/7S22/GGGGG15DDDD6/30/30/30/30/ggggg15dddd6/30/30/30/30/30/30/30/30/30/30/14pk14/30 r 12 7.11 - - - - -",
      solution: [{ fc: 7, fr: 11, c: 10, r: 11 }, { fc: 10, fr: 10, c: 13, r: 10 }],
    },
    {
      id: "t4", chapter: 1, name: "渡河",
      hint: "只有军棋能过河，且必须走直线：纵向需步长 ≥3，横向需步长 ≥4，同一河段内不能有别的棋子。把军棋推进到蓝方半场。",
      ai: 3,
      goal: {type: "reach", side: "red", pieceType: "general", zone: {r0: 16, r1: 29, c0: 0, c1: 29}, moveLimit: 1},
      fen: "FHQ1 14PK14/30/30/30/30/30/30/30/30/30/30/30/30/10G19/30/30/30/30/30/30/30/30/30/30/30/30/30/30/14pk14/30 r 15 - - - - - -",
      solution: [{ fc: 10, fr: 13, c: 10, r: 16 }],
    },
    {
      id: "t5", chapter: 1, name: "继位与复活",
      hint: "母棋阵亡后子棋继位（横纵 2 步、可吃子）。继位母棋每吃一子，就在原地复活一枚白板棋。吃掉下方的蓝军棋试试。",
      ai: 3,
      goal: {type: "slay", side: "red", target: "general", moveLimit: 1},
      fen: "FHQ1 30/30/30/30/30/30/30/30/30/30/10Q19/30/10g19/30/30/30/30/30/30/30/30/30/30/30/30/30/30/30/14pk14/30 r 26 - - - - - -",
      solution: [{ fc: 10, fr: 10, c: 10, r: 12 }],
    },
    {
      id: "c1", chapter: 2, name: "破盾阵",
      hint: "盾墙横在面前。绕到同一行再侧击，拿下两枚盾棋。",
      ai: 3,
      goal: {type: "slay", side: "red", target: "shield", count: 2, moveLimit: 16},
      fen: "FHQ1 14PK14/30/30/30/30/30/30/30/30/30/4G15G9/30/4d7d7d5d3/30/30/30/30/30/30/30/8g6g6g7/30/30/30/30/30/30/30/14pk14/30 r 18 - - - - - -",
    },
    {
      id: "c2", chapter: 2, name: "斩首",
      hint: "越过河道，取下蓝方母棋或子棋任意一枚。注意盾棋会在正前方 1~3 格内为同列棋子撑起保护。",
      ai: 2,
      goal: {type: "slay", side: "red", target: "royal", count: 1, moveLimit: 24},
      fen: "FHQ1 14PK14/30/30/30/30/30/30/30/30/30/4S3G6G6G7/30/30/30/30/30/30/30/30/30/10g19/30/30/30/30/30/30/30/14pk14/10d9d9 r 20 4.10 - - - - -",
    },
    {
      id: "c3", chapter: 2, name: "孤城",
      hint: "三路蓝军压境，你只有一枚军棋。母子俱亡即败——撑过 8 个回合。",
      ai: 1,
      goal: {type: "survive", side: "red", rounds: 8},
      fen: "FHQ1 14PK14/30/30/30/30/30/30/30/30/30/15G14/30/30/30/30/30/30/30/8g6g6g7/30/30/30/30/30/30/30/30/30/14pk14/30 r 22 - - - - - -",
    },
    {
      id: "c4", chapter: 2, name: "三军会猎",
      hint: "对面三路军棋，逐个歼灭。你的军棋步长会随蓝方军棋阵亡而增长。",
      ai: 2,
      goal: {type: "slay", side: "red", target: "general", count: 3, moveLimit: 32},
      fen: "FHQ1 14PK14/30/30/30/30/30/30/30/30/30/4S3G6G6G7/30/30/30/30/30/30/30/8g6g6g7/30/30/30/8g6g6g7/30/30/30/6d5d5d5d5/30/14pk14/30 r 24 4.10 - - - - -",
    },
    {
      id: "c5", chapter: 2, name: "终局",
      hint: "先杀子棋，再取母棋：若母棋先亡，子棋会继位，且继位母棋每吃一子就复活一枚白板。子棋被同列盾棋护住，先沿底行横向拆掉那枚盾。",
      ai: 2,
      goal: {type: "standard", side: "red", moveLimit: 70},
      fen: "FHQ1 14PK14/30/30/30/30/30/30/30/30/30/4S3G3G5G3G7/30/30/30/30/30/30/30/30/30/30/30/30/30/30/30/30/30/14pk14/14d1d13 r 28 4.10 - - - - -",
    },
    {
      id: "c6", chapter: 2, name: "缺口穿渡",
      hint: "河道并非连成一片：每 8 列留 2 列缺口。探棋横纵 4 步，可从缺口直插对岸；但身边贴着棋子时过不去。",
      ai: 2,
      goal: {type: "reach", side: "red", pieceType: "scout", zone: {r0: 16, r1: 29, c0: 0, c1: 29}, moveLimit: 12},
      fen: "FHQ1 14PK14/30/30/30/30/30/30/30/30/30/30/22G7/14S15/30/30/30/30/30/30/30/8g21/30/20g9/30/30/30/12d5d11/30/14pk14/30 r 16 14.12 - - - - -",
    },
    {
      id: "c7", chapter: 2, name: "白板反攻",
      hint: "你的母棋已亡、子棋继位。继位母棋横纵 2 步、能吃子，且每吃一子就在原地复活一枚白板棋——吃得越多，队伍越长。",
      ai: 2,
      goal: {type: "slay", side: "red", target: "general", count: 2, moveLimit: 14},
      fen: "FHQ1 30/30/30/30/30/30/30/30/30/30/15Q14/30/12g5g11/30/15g14/30/30/30/30/30/30/30/30/30/30/30/6d17d5/30/14pk14/30 r 24 - - - - - -",
    },
    {
      id: "c8", chapter: 2, name: "执蓝守土",
      hint: "这局你执蓝方（棋盘下方）。红方三路军棋压过河来，你只有两枚军棋加一道盾——撑过 10 个回合。",
      ai: 2,
      goal: {type: "survive", side: "blue", rounds: 10},
      fen: "FHQ1 14PK14/30/30/30/30/30/30/30/30/30/30/30/8G6G6G7/30/30/30/30/30/30/30/10g9g9/30/30/30/15d14/30/30/30/14pk14/30 r 20 - - - - - -",
    },
    {
      id: "c9", chapter: 2, name: "无雾终盘",
      hint: "全场棋子已不足 20 枚：迷雾自然解除、全体自由行动，没有许可区可躲。硬碰硬把对面军棋清干净。",
      ai: 2,
      goal: {type: "slay", side: "red", target: "general", count: 3, moveLimit: 28},
      fen: "FHQ1 14PK14/30/30/30/30/30/30/30/30/30/6G5G5G5G5/30/30/30/30/30/30/30/30/8g7g13/30/22g7/30/30/30/30/30/15d14/14pk14/30 r 30 - - - - - -",
    },
    {
      id: "c10", chapter: 2, name: "孤子直取",
      hint: "蓝方只剩母子与一枚盾。盾在子棋正前方为它撑起保护，而盾自己只能被横向吃——先沿同一行拆掉它。",
      ai: 2,
      goal: {type: "slay", side: "red", target: "royal", count: 1, moveLimit: 20},
      fen: "FHQ1 14PK14/30/30/30/30/30/30/30/30/30/4S5G9G9/30/30/30/30/30/30/30/30/30/30/30/30/30/30/30/14d15/30/14pk14/30 r 26 4.10 - - - - -",
    },
  ];
  const CHAPTERS = [
    { id: 1, name: "教学 · 识阵", desc: "五节小课，认识走子、盾防、许可区、渡河与继位" },
    { id: 2, name: "官方残局", desc: "对手会真的思考；每局均经机器实测可解" },
  ];
  const byId = id => LEVELS.filter(function (l) { return l.id === id; })[0] || null;
  const ofChapter = ch => LEVELS.filter(function (l) { return l.chapter === ch; });
  const api = { LEVELS, CHAPTERS, byId, ofChapter };
  if (typeof module !== "undefined" && module.exports) module.exports = api;
  else root.Levels = api;
})(typeof window !== "undefined" ? window : globalThis);
