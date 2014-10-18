/**
 * @CZheng 
 * Calculate the node distance between two proteins in the reference database 
 */
import java.util.*;
import java.io.*;
public class CalcInteractionDis {
	private static final String DB_DIRECTORY = "data/";                                //Directory of reference database storage
	public static void main(String[] args) {
		removeDup(args[0],args[0] + "dup");
		Map<String, Node> graphMap = buildMap(DB_DIRECTORY + args[1]);
		getDis(args[0] + "dup", args[0] + "known", graphMap);
		Map<Interaction, String> interMap = buildInteractionMap(args[0] + "known");
		output(args[0], args[0] + "fin", interMap);
		
	}
	public static void removeDup(String input, String output) {                        //remove duplicate entries in the data table
		if (input == null || output == null) return;
		ArrayList<Interaction> aInteraction = new ArrayList<Interaction>();
		Set<Interaction> interactionSet = new HashSet<Interaction>();
		try{
			FileReader fr = new FileReader(input);
			BufferedReader br = new BufferedReader(fr);
			FileOutputStream fout = new FileOutputStream(output);
			PrintStream ps = new PrintStream(fout);
			String line = br.readLine();
			while(line != null) {
				if(line.isEmpty()){
					line = br.readLine();
					continue;
				}
				String[] arr = line.split("\t");
				Interaction i = new Interaction(arr[1], arr[13]);
				//System.out.println(arr[1] + "\t" + arr[13]);
				if(!interactionSet.contains(i)) {
					aInteraction.add(i);
					interactionSet.add(i);
				}
				line = br.readLine();
			}
			for(Interaction i : interactionSet) {
				ps.println(i.proA + "\t" + i.proB);
			}
			ps.close();
			fout.close();
			br.close();
			fr.close();
		} catch (IOException e) {
			System.err.println(e.getMessage());
		}	
	}
	
	public static void getDis(String input, String output, Map<String, Node> graphMap) { //Output distance
		try {
			FileReader fr = new FileReader(input);
			BufferedReader br = new BufferedReader(fr);
			FileOutputStream fout = new FileOutputStream(output);
			PrintStream ps = new PrintStream(fout);
			String line = br.readLine();
			while (line != null) {
				String[] arr = line.split("\t");
				String distance = checkDistance(arr[0], arr[1], graphMap);
				ps.println(line + "\t" + distance);
				line = br.readLine();
			}
			ps.close();
			fout.close();
			br.close();
			fr.close();
		} catch (IOException e) {
			System.err.println(e.getMessage());
		}
	}
	public static Map<Interaction, String> buildInteractionMap(String in) {                      //build a map for interaction distance
		Map<Interaction, String> interSet = new HashMap<Interaction, String>();
		try {
			FileReader fr = new FileReader(in);
			BufferedReader br = new BufferedReader(fr);
			String l = br.readLine();
			while (l != null) {
				String[] arr = l.split("\t");
				Interaction inter = new Interaction(arr[0].trim(), arr[1].trim());
				interSet.put(inter, arr[2].trim());
				l = br.readLine();
			}			
			br.close();
			fr.close();				
		} catch (IOException e) {
			System.err.println(e.getMessage());
		}
		return interSet;
	}
	public static Map<String, Node> buildMap(String input) {                             //build connection graph based on the reference database
		Map<String, Node> graphMap = new HashMap<String, Node>();
		try {
			FileReader  fr = new FileReader(input);
			BufferedReader br = new BufferedReader(fr);
			String line = br.readLine();
			while (line != null) {
				String[] arr = line.split("\t");				
				Node n,m;
				if (!graphMap.containsKey(arr[0])) {
					n = new Node(arr[0]);
					graphMap.put(arr[0], n);
				} else {
					n = graphMap.get(arr[0]);
				}
				if (!graphMap.containsKey(arr[1])) {
					m = new Node(arr[1]);
					graphMap.put(arr[1], m);
				} else {
					m = graphMap.get(arr[1]);
				}
				n.daughters.add(m);
				m.daughters.add(n);
				line = br.readLine();
			}
			br.close();
			fr.close();
		} catch (IOException e) {
			System.err.println(e.getMessage());
		}
		return graphMap;
	}
	public static String checkDistance(String a, String b, Map<String, Node> graphMap) { //calculate distance
		Set<Node> visited = new HashSet<Node>();
		if(!graphMap.containsKey(a)) return "N/A";
		ArrayList<Node> daughters = graphMap.get(a).daughters;
		return checkDis(b, daughters, visited, "0");
	}
	public static String checkDis(String b, ArrayList<Node> parents, Set<Node> visited, String distance) {
		if (parents.isEmpty()) return "N/A";
		ArrayList<Node> daughters = new ArrayList<Node>();
		for (Node n : parents) {
			if(n.value.equals(b)) return distance;
			visited.add(n);
			for (Node m : n.daughters) {
				if (!visited.contains(m)) {
					daughters.add(m);
				}
			}
		}
		return checkDis(b, daughters, visited, String.valueOf(Integer.valueOf(distance) + 1));
	}
	public static void output(String in, String out, Map<Interaction, String>interMap) {
		try {
			FileReader fr = new FileReader(in);
			BufferedReader br = new BufferedReader(fr);
			FileOutputStream fout = new FileOutputStream(out);
			PrintStream ps = new PrintStream(fout);
			String line = br.readLine();
			while (line != null) {
				String[] arr = line.split("\t");
				if (arr.length < 14) break;
				Interaction i = new Interaction(arr[1], arr[13]);
				String dis = interMap.get(i);
				if (arr[1].trim().equals(arr[13].trim())) {
					if (arr[10].trim().equals("null") || arr[22].trim().equals("null") || arr[10].trim().equals("####") || arr[22].trim().equals("####")) {
						dis = "intra";
					} else {
						int beg1 = Integer.valueOf(arr[8].trim());
						int end1 = beg1 + arr[0].trim().length();
						int beg2 = Integer.valueOf(arr[20].trim());
						int end2 = beg2 + arr[12].trim().length();
						if ((beg1 < beg2 && end1 < beg2) || (beg2 < beg1 && end2 < beg1)) {
							if (arr[10].trim().split(":")[1].equals(arr[22].trim().split(":")[1])) dis = "intra";
						}
					}
				}
				ps.println(line + "\t" + dis);
				line = br.readLine();
			}
			ps.close();
			fout.close();
			br.close();
			fr.close();
		} catch (IOException e) {
			System.err.println(e.getMessage());
		}
	}
}

class Node{
	public String value;
	public ArrayList<Node> daughters;
	public Node(String s) {
		value = s;
		daughters = new ArrayList<Node>();
	}	
}
class Interaction{
	public String proA, proB;
	public Interaction(String a, String b) {
		proA = a;
		proB = b;
	}
	@Override
	public int hashCode() {
		String s;
		int code = 0;
		if (proA.compareTo(proB) >= 0) s = proA + proB;
		else s = proB + proA;
		for (int i = 0; i < s.length(); i++) {
			code = code * 31 + Integer.valueOf(s.charAt(i));
		}
		return code;
	}
	@Override
	public boolean equals(Object o) {
		Interaction i = (Interaction) o;
		String s, t;
		if (proA.compareTo(proB) >= 0) s = proA + proB;
		else s = proB + proA; 
		if (i.proA.compareTo(i.proB) >= 0) t = i.proA + i.proB;
		else t = i.proB + i.proA;
		return s.equals(t);
	}
}
